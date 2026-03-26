<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplaceSaleItemsRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Sale;
use App\Services\SaleService;
use DomainException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        $sales = $this->saleQuery($filters)
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'filters' => $filters,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->extractFilters($request);
        $filename = 'vendas-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($filters): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['Numero', 'Cliente', 'Status', 'Itens', 'Subtotal', 'Desconto', 'Total', 'Criado em'], ';');

            $this->saleQuery($filters)
                ->chunk(500, function ($sales) use ($output): void {
                    foreach ($sales as $sale) {
                        fputcsv($output, [
                            (string) $sale->id,
                            $sale->client?->name ?? '-',
                            (string) $sale->status,
                            (string) $sale->items->count(),
                            number_format((float) $sale->subtotal, 2, '.', ''),
                            number_format((float) $sale->discount, 2, '.', ''),
                            number_format((float) $sale->total, 2, '.', ''),
                            $sale->created_at?->format('d/m/Y H:i:s') ?? '',
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
        $sales = $this->saleQuery($filters)->get();

        $pdf = Pdf::loadView('pdf.sales-report', [
            'sales' => $sales,
            'filters' => $filters,
        ])->setPaper('a4');

        return $pdf->stream('vendas-'.now()->format('Ymd-His').'.pdf');
    }

    public function create(): View
    {
        return view('sales.form', [
            'sale' => null,
            'clients' => Client::query()->orderBy('name')->get(),
            'products' => CatalogItem::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $sale = Sale::query()->create([
            'client_id' => $data['client_id'] ?? null,
            'discount' => $data['discount'] ?? 0,
            'status' => 'pendente',
            'created_by_user_id' => $request->user()?->id,
        ]);

        $items = $this->normalizedItems($request->input('items', []));
        if ($items !== []) {
            $this->saleService->replaceItems($sale, $items);
        }

        return redirect()->route('sales.index')->with('success', 'Venda criada com sucesso!');
    }

    public function edit(Sale $sale): View
    {
        $sale->load('items');

        return view('sales.form', [
            'sale' => $sale,
            'clients' => Client::query()->orderBy('name')->get(),
            'products' => CatalogItem::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateItems(ReplaceSaleItemsRequest $request, Sale $sale): RedirectResponse
    {
        try {
            $this->saleService->replaceItems($sale, $request->validated()['items']);
        } catch (DomainException $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        return redirect()->route('sales.index')->with('success', 'Itens da venda atualizados com sucesso!');
    }

    public function confirm(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'installments' => ['nullable', 'integer', 'min:1', 'max:24'],
            'first_due_date' => ['nullable', 'date'],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $installments = (int) ($data['installments'] ?? 1);
        if ($installments > 1 && empty($data['first_due_date'])) {
            return back()->withErrors(['sale' => 'Informe a data de primeiro vencimento para parcelamento.'])->withInput();
        }

        try {
            $this->saleService->confirmSale($sale, $request->user()?->id, [
                'installments' => $installments,
                'first_due_date' => $data['first_due_date'] ?? null,
                'interval_days' => (int) ($data['interval_days'] ?? 30),
            ]);
        } catch (DomainException $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        return redirect()->route('sales.index')->with('success', 'Venda confirmada com sucesso!');
    }

    public function printPreview(Sale $sale): View
    {
        $sale->load(['client', 'items', 'createdBy']);

        return view('pdf.sale', [
            'sale' => $sale,
            'previewMode' => true,
            'companyLogoDataUri' => $this->companyLogoDataUri(false),
        ]);
    }

    public function printPdf(Sale $sale): Response
    {
        $sale->load(['client', 'items', 'createdBy']);

        $pdf = Pdf::loadView('pdf.sale', [
            'sale' => $sale,
            'previewMode' => false,
            'companyLogoDataUri' => $this->companyLogoDataUri(true),
        ])->setPaper('a4');

        return $pdf->stream('venda-'.$sale->id.'.pdf');
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizedItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && ((float) ($item['quantity'] ?? 0) > 0))
            ->map(function (array $item): array {
                return [
                    'catalog_item_id' => $item['catalog_item_id'] ?: null,
                    'item_name' => $item['item_name'] ?? null,
                    'item_type' => $item['item_type'] ?? null,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'unit_price' => $item['unit_price'] === '' ? null : (float) ($item['unit_price'] ?? 0),
                ];
            })
            ->values()
            ->all();
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

        $allowedSortBy = ['id', 'created_at', 'status', 'total'];
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'min_total' => is_numeric($request->query('min_total')) ? (string) $request->query('min_total') : '',
            'max_total' => is_numeric($request->query('max_total')) ? (string) $request->query('max_total') : '',
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
        ];
    }

    private function saleQuery(array $filters): Builder
    {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $minTotal = $filters['min_total'] ?? '';
        $maxTotal = $filters['max_total'] ?? '';
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return Sale::query()
            ->with(['client', 'items'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['pendente', 'confirmada'], true), fn ($query) => $query->where('status', $status))
            ->when(is_numeric($minTotal), fn ($query) => $query->where('total', '>=', (float) $minTotal))
            ->when(is_numeric($maxTotal), fn ($query) => $query->where('total', '<=', (float) $maxTotal))
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc');
    }
}
