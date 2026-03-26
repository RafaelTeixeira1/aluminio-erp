<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplaceQuoteItemsRequest;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\DesignSketch;
use App\Models\Quote;
use App\Services\QuoteDocumentSettingsService;
use App\Services\QuoteService;
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly SaleService $saleService,
        private readonly QuoteDocumentSettingsService $quoteDocumentSettingsService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        $quotes = $this->quoteQuery($filters)
            ->paginate(15)
            ->withQueryString();

        return view('quotes.index', [
            'quotes' => $quotes,
            'filters' => $filters,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->extractFilters($request);
        $filename = 'orcamentos-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($filters): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['Numero', 'Cliente', 'Status', 'Validade', 'Subtotal', 'Desconto', 'Total', 'Criado em'], ';');

            $this->quoteQuery($filters)
                ->chunk(500, function ($quotes) use ($output): void {
                    foreach ($quotes as $quote) {
                        fputcsv($output, [
                            (string) $quote->id,
                            $quote->client?->name ?? 'Orcamento rapido',
                            (string) $quote->status,
                            $quote->valid_until?->format('d/m/Y') ?? '',
                            number_format((float) $quote->subtotal, 2, '.', ''),
                            number_format((float) $quote->discount, 2, '.', ''),
                            number_format((float) $quote->total, 2, '.', ''),
                            $quote->created_at?->format('d/m/Y H:i:s') ?? '',
                        ], ';');
                    }
                });

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    public function exportPdf(Request $request): Response
    {
        $filters = $this->extractFilters($request);
        $quotes = $this->quoteQuery($filters)->get();

        $pdf = Pdf::loadView('pdf.quotes-report', [
            'quotes' => $quotes,
            'filters' => $filters,
        ])->setPaper('a4');

        return $pdf->stream('orcamentos-'.now()->format('Ymd-His').'.pdf');
    }

    public function create(): View
    {
        return view('quotes.form', [
            'quote' => null,
            'inlineSketch' => null,
            'clients' => Client::query()->orderBy('name')->get(),
            'products' => CatalogItem::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $quote = Quote::query()->create([
            ...$data,
            'status' => $data['status'] ?? 'aberto',
            'discount' => $data['discount'] ?? 0,
            'created_by_user_id' => $request->user()?->id,
        ]);

        $items = $this->normalizedItems($request);
        if ($items !== []) {
            $this->quoteService->replaceItems($quote, $items);
        } else {
            $this->quoteService->recalculate($quote);
        }

        $this->syncInlineSketch($request, $quote);

        return redirect()->route('quotes.index')->with('success', 'Orcamento criado com sucesso!');
    }

    public function edit(Quote $quote): View
    {
        $quote->load('items');
        $inlineSketch = Schema::hasTable('design_sketches')
            ? DesignSketch::query()->where('quote_id', $quote->id)->orderByDesc('updated_at')->first()
            : null;

        return view('quotes.form', [
            'quote' => $quote,
            'inlineSketch' => $inlineSketch,
            'clients' => Client::query()->orderBy('name')->get(),
            'products' => CatalogItem::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(StoreQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $quote->update($request->validated());

        $items = $this->normalizedItems($request);
        if ($items !== []) {
            $this->quoteService->replaceItems($quote, $items);
        } else {
            $this->quoteService->recalculate($quote);
        }

        $this->syncInlineSketch($request, $quote);

        return redirect()->route('quotes.index')->with('success', 'Orcamento atualizado com sucesso!');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Orcamento removido com sucesso!');
    }

    public function convert(Request $request, Quote $quote): RedirectResponse
    {
        try {
            $this->saleService->createFromQuote($quote, $request->user()?->id);
        } catch (DomainException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return redirect()->route('sales.index')->with('success', 'Orcamento convertido em venda!');
    }

    public function duplicate(Request $request, Quote $quote): RedirectResponse
    {
        $quote->load(['items', 'designSketches', 'pieceDesigns']);

        $newQuote = Quote::query()->create([
            'client_id' => $quote->client_id,
            'created_by_user_id' => $request->user()?->id,
            'status' => 'aberto',
            'subtotal' => 0,
            'discount' => (float) $quote->discount,
            'total' => 0,
            'valid_until' => $quote->valid_until,
            'payment_method' => $quote->payment_method,
            'notes' => $quote->notes,
            'item_quantification_notes' => $quote->item_quantification_notes,
        ]);

        $items = $quote->items->map(function ($item): array {
            return [
                'catalog_item_id' => $item->catalog_item_id,
                'item_name' => $item->item_name,
                'item_type' => $item->item_type,
                'quantity' => (float) $item->quantity,
                'unit_price' => $item->unit_price === null ? null : (float) $item->unit_price,
                'width_mm' => $item->width_mm === null ? null : (float) $item->width_mm,
                'height_mm' => $item->height_mm === null ? null : (float) $item->height_mm,
                'metadata' => is_array($item->metadata) ? $item->metadata : null,
            ];
        })->all();

        if ($items !== []) {
            $this->quoteService->replaceItems($newQuote, $items);
        } else {
            $this->quoteService->recalculate($newQuote);
        }

        if ($quote->designSketches->isNotEmpty()) {
            $newQuote->designSketches()->createMany(
                $quote->designSketches->map(fn ($sketch): array => [
                    'created_by_user_id' => $request->user()?->id,
                    'title' => $sketch->title,
                    'width_mm' => $sketch->width_mm,
                    'height_mm' => $sketch->height_mm,
                    'canvas_json' => $sketch->canvas_json,
                    'preview_png' => $sketch->preview_png,
                    'notes' => $sketch->notes,
                ])->all()
            );
        } elseif ($quote->pieceDesigns->isNotEmpty()) {
            // Legacy fallback for quotes that still only have piece_designs.
            $newQuote->designSketches()->createMany(
                $quote->pieceDesigns->values()->map(function ($piece, int $index) use ($newQuote, $request): array {
                    return [
                        'created_by_user_id' => $request->user()?->id,
                        'title' => 'Peca '.$newQuote->id.'-'.($index + 1),
                        'width_mm' => $piece->width_mm,
                        'height_mm' => $piece->height_mm,
                        'canvas_json' => json_encode([
                            'legacy_piece_design' => true,
                            'width_mm' => (float) $piece->width_mm,
                            'height_mm' => (float) $piece->height_mm,
                            'quantity' => (float) $piece->quantity,
                            'data_json' => is_array($piece->data_json) ? $piece->data_json : null,
                        ], JSON_UNESCAPED_UNICODE),
                        'preview_png' => null,
                        'notes' => 'Migrado automaticamente de detalhamento de peca legado.',
                    ];
                })->all()
            );
        }

        return redirect()->route('quotes.edit', $newQuote)->with('success', 'Orcamento duplicado com sucesso!');
    }

    public function printPreview(Quote $quote): View
    {
        $quote->load(['client', 'items', 'pieceDesigns', 'designSketches', 'createdBy']);

        return view('pdf.quote', [
            'quote' => $quote,
            'previewMode' => true,
            'companyLogoDataUri' => $this->companyLogoDataUri(false),
            'quoteSettings' => $this->quoteDocumentSettingsService->load(),
        ]);
    }

    public function printPdf(Quote $quote): Response
    {
        $quote->load(['client', 'items', 'pieceDesigns', 'designSketches', 'createdBy']);

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'previewMode' => false,
            'companyLogoDataUri' => $this->companyLogoDataUri(true),
            'quoteSettings' => $this->quoteDocumentSettingsService->load(),
        ])->setPaper('a4');

        return $pdf->stream('orcamento-'.$quote->id.'.pdf');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizedItems(Request $request): array
    {
        /** @var array<int, mixed> $items */
        $items = $request->input('items', []);

        $catalogIds = collect($items)
            ->pluck('catalog_item_id')
            ->filter(fn ($id) => is_scalar($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $catalogItemMap = $catalogIds->isEmpty()
            ? collect()
            : CatalogItem::query()
                ->whereIn('id', $catalogIds)
                ->get(['id', 'name', 'image_path', 'weight_per_meter_kg'])
                ->keyBy('id');

        return collect($items)
            ->filter(fn ($item) => is_array($item) && !empty($item['catalog_item_id']) && ((float) ($item['quantity'] ?? 0) > 0))
            ->map(function (array $item, int $index) use ($request, $catalogItemMap): array {
                $metadata = [];
                $catalogItem = null;
                if (!empty($item['catalog_item_id'])) {
                    $catalogItem = $catalogItemMap->get((int) $item['catalog_item_id']);
                }

                $uploadedImage = data_get($request->file('items', []), $index.'.image');
                if ($uploadedImage instanceof UploadedFile) {
                    $storedPath = $uploadedImage->store('quote-items', 'public');
                    $metadata['image'] = 'storage/'.$storedPath;
                } elseif (!empty($item['remove_image'])) {
                    // Explicitly removed by user in edit form.
                } elseif (!empty($item['existing_image']) && is_string($item['existing_image'])) {
                    $metadata['image'] = $item['existing_image'];
                } elseif (!empty($item['catalog_item_id'])) {
                    $catalogImage = $catalogItem?->image_path;
                    if (is_string($catalogImage) && trim($catalogImage) !== '') {
                        $metadata['image'] = $catalogImage;
                    }
                }

                if (isset($item['weight_per_meter_kg']) && is_numeric($item['weight_per_meter_kg'])) {
                    $metadata['weight_per_meter_kg'] = (float) $item['weight_per_meter_kg'];
                } elseif ($catalogItem !== null && is_numeric($catalogItem->effective_weight_per_meter_kg)) {
                    $metadata['weight_per_meter_kg'] = (float) $catalogItem->effective_weight_per_meter_kg;
                }

                $bnf = trim((string) ($item['bnf'] ?? ''));
                if ($bnf !== '') {
                    $metadata['bnf'] = $bnf;
                }

                $barCut = trim((string) ($item['bar_cut_size'] ?? ''));
                if ($barCut !== '') {
                    $metadata['bar_cut_size'] = $barCut;
                }

                if (($item['pieces_quantity'] ?? '') !== '') {
                    $metadata['pieces_quantity'] = (float) $item['pieces_quantity'];
                }

                if (($item['weight'] ?? '') !== '') {
                    $metadata['weight'] = (float) $item['weight'];
                }

                if (($item['total_weight'] ?? '') !== '') {
                    $metadata['total_weight'] = (float) $item['total_weight'];
                }

                $itemObservation = trim((string) ($item['item_observation'] ?? ''));
                if ($itemObservation !== '') {
                    $metadata['item_observation'] = $itemObservation;
                }

                return [
                    'catalog_item_id' => $item['catalog_item_id'] ?: null,
                    'item_name' => null,
                    'item_type' => null,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'unit_price' => null,
                    'width_mm' => (($item['width_mm'] ?? '') === '') ? null : (float) ($item['width_mm'] ?? 0),
                    'height_mm' => (($item['height_mm'] ?? '') === '') ? null : (float) ($item['height_mm'] ?? 0),
                    'metadata' => $metadata !== [] ? $metadata : null,
                ];
            })
            ->filter(fn (array $item) => !empty($item['catalog_item_id']) && ((float) ($item['quantity'] ?? 0) > 0))
            ->values()
            ->all();
    }

    public function sendEmail(Request $request, Quote $quote): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $emailService = app(\App\Services\EmailService::class);
        $emailService->sendQuoteEmail($quote, $request->input('email'));

        return back()->with('success', 'Orçamento enviado por email com sucesso!');
    }

    private function syncInlineSketch(Request $request, Quote $quote): void
    {
        if (!Schema::hasTable('design_sketches')) {
            return;
        }

        $enabled = $request->boolean('sketch_enabled');
        $existingSketchId = $request->integer('sketch_id');

        $existingSketch = null;
        if ($existingSketchId > 0) {
            $existingSketch = DesignSketch::query()
                ->where('id', $existingSketchId)
                ->where('quote_id', $quote->id)
                ->first();
        }

        if (!$enabled) {
            if ($existingSketch !== null) {
                $existingSketch->delete();
            }

            return;
        }

        $canvasJson = trim((string) $request->input('sketch_canvas_json', ''));
        if ($canvasJson === '') {
            return;
        }

        $payload = [
            'quote_id' => $quote->id,
            'created_by_user_id' => $quote->created_by_user_id,
            'title' => trim((string) $request->input('sketch_title', 'Desenho integrado orçamento #'.$quote->id)) ?: 'Desenho integrado orçamento #'.$quote->id,
            'width_mm' => is_numeric($request->input('sketch_width_mm')) ? (float) $request->input('sketch_width_mm') : null,
            'height_mm' => is_numeric($request->input('sketch_height_mm')) ? (float) $request->input('sketch_height_mm') : null,
            'canvas_json' => $canvasJson,
            'preview_png' => trim((string) $request->input('sketch_preview_png', '')) ?: null,
            'notes' => trim((string) $request->input('sketch_notes', '')) ?: null,
        ];

        if ($existingSketch !== null) {
            $existingSketch->update($payload);
            return;
        }

        DesignSketch::query()->create($payload);
    }

    private function companyLogoDataUri(bool $preferPdfSafe = false): ?string
    {
        $pdfSafeFirst = [
            ['path' => public_path('images/company-logo.jpg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.jpeg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.svg'), 'mime' => 'image/svg+xml'],
            ['path' => public_path('images/company-logo.png'), 'mime' => 'image/png'],
        ];
        $rasterFirst = [
            ['path' => public_path('images/company-logo.png'), 'mime' => 'image/png'],
            ['path' => public_path('images/company-logo.jpg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.jpeg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.svg'), 'mime' => 'image/svg+xml'],
        ];

        $candidates = $preferPdfSafe ? $pdfSafeFirst : $rasterFirst;

        foreach ($candidates as $candidate) {
            if (!is_file($candidate['path'])) {
                continue;
            }

            $content = file_get_contents($candidate['path']);
            if ($content === false) {
                continue;
            }

            return 'data:'.$candidate['mime'].';base64,'.base64_encode($content);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(Request $request): array
    {
        $sortBy = (string) $request->query('sort_by', 'created_at');
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc'));

        $allowedSortBy = ['id', 'created_at', 'valid_until', 'status', 'total'];
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'valid_from' => (string) $request->query('valid_from', ''),
            'valid_to' => (string) $request->query('valid_to', ''),
            'min_total' => is_numeric($request->query('min_total')) ? (string) $request->query('min_total') : '',
            'max_total' => is_numeric($request->query('max_total')) ? (string) $request->query('max_total') : '',
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
        ];
    }

    private function quoteQuery(array $filters): Builder
    {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $validFrom = $filters['valid_from'] ?? '';
        $validTo = $filters['valid_to'] ?? '';
        $minTotal = $filters['min_total'] ?? '';
        $maxTotal = $filters['max_total'] ?? '';
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return Quote::query()
            ->with(['client', 'items'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    if (is_numeric($search)) {
                        $nested->orWhere('id', (int) $search);
                    }

                    $nested
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('document', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery
                                ->where('item_name', 'like', "%{$search}%")
                                ->orWhereHas('catalogItem', fn ($catalogQuery) => $catalogQuery->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when(in_array($status, ['aberto', 'aprovado', 'convertido', 'cancelado', 'expirado'], true), fn ($query) => $query->where('status', $status))
            ->when($validFrom !== '', fn ($query) => $query->whereDate('valid_until', '>=', $validFrom))
            ->when($validTo !== '', fn ($query) => $query->whereDate('valid_until', '<=', $validTo))
            ->when(is_numeric($minTotal), fn ($query) => $query->where('total', '>=', (float) $minTotal))
            ->when(is_numeric($maxTotal), fn ($query) => $query->where('total', '<=', (float) $maxTotal))
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc');
    }
}
