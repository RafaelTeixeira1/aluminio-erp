<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\StockMovement;
use App\Services\StockService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function index(Request $request): View
    {
        $search = (string) $request->string('search');
        $status = $request->string('status')->value();
        $movementItemId = $request->integer('movement_item_id');
        $movementType = $request->string('movement_type')->value();
        $movementFrom = $request->string('movement_from')->value();
        $movementTo = $request->string('movement_to')->value();

        $items = CatalogItem::query()
            ->with('category:id,name')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->when($status === 'baixo', fn ($q) => $q->whereColumn('stock', '<=', 'stock_minimum'))
            ->when($status === 'normal', fn ($q) => $q->whereColumn('stock', '>', 'stock_minimum'))
            ->when($status === 'sem_estoque', fn ($q) => $q->where('stock', '<=', 0))
            ->orderBy('name')
            ->paginate(20, ['*'], 'items_page')
            ->withQueryString();

        $summary = [
            'total_items' => (int) CatalogItem::query()->count(),
            'total_stock' => (float) (CatalogItem::query()->sum('stock') ?? 0),
            'low_stock' => (int) CatalogItem::query()->whereColumn('stock', '<=', 'stock_minimum')->count(),
            'out_of_stock' => (int) CatalogItem::query()->where('stock', '<=', 0)->count(),
        ];

        $movements = $this->movementQuery($movementItemId, $movementType, $movementFrom, $movementTo)
            ->latest('created_at')
            ->paginate(12, ['*'], 'movements_page')
            ->withQueryString();

        $products = CatalogItem::query()->active()->orderBy('name')->get(['id', 'name', 'stock']);

        return view('stock.index', [
            'items' => $items,
            'summary' => $summary,
            'movements' => $movements,
            'products' => $products,
            'canManageStock' => in_array((string) ($request->user()?->profile ?? ''), ['admin', 'estoquista'], true),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'movementFilters' => [
                'movement_item_id' => $movementItemId > 0 ? $movementItemId : null,
                'movement_type' => $movementType,
                'movement_from' => $movementFrom,
                'movement_to' => $movementTo,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $movementItemId = $request->integer('movement_item_id');
        $movementType = $request->string('movement_type')->value();
        $movementFrom = $request->string('movement_from')->value();
        $movementTo = $request->string('movement_to')->value();

        $filename = 'historico-estoque-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($movementItemId, $movementType, $movementFrom, $movementTo): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['Data', 'Produto', 'Tipo', 'Quantidade', 'Estoque Antes', 'Estoque Depois', 'Observacao', 'Usuario'], ';');

            $this->movementQuery($movementItemId, $movementType, $movementFrom, $movementTo)
                ->latest('created_at')
                ->chunk(500, function ($rows) use ($output): void {
                    foreach ($rows as $movement) {
                        fputcsv($output, [
                            optional($movement->created_at)->format('d/m/Y H:i:s'),
                            $movement->catalogItem?->name ?? '-',
                            $movement->movement_type,
                            number_format((float) $movement->quantity, 3, '.', ''),
                            number_format((float) $movement->stock_before, 3, '.', ''),
                            number_format((float) $movement->stock_after, 3, '.', ''),
                            $movement->notes ?? '',
                            $movement->user?->name ?? '-',
                        ], ';');
                    }
                });

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    public function entry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $item = CatalogItem::query()->findOrFail($data['catalog_item_id']);
        $this->stockService->entry($item, (float) $data['quantity'], $request->user()?->id, $data['notes'] ?? null, 'manual', null);

        return redirect()->route('stock.index')->with('success', 'Entrada registrada com sucesso.');
    }

    public function output(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $item = CatalogItem::query()->findOrFail($data['catalog_item_id']);

        try {
            $this->stockService->manualOutput($item, (float) $data['quantity'], $request->user()?->id, $data['notes'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['stock' => $e->getMessage()])->withInput();
        }

        return redirect()->route('stock.index')->with('success', 'Saida registrada com sucesso.');
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'new_stock' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $item = CatalogItem::query()->findOrFail($data['catalog_item_id']);
        $this->stockService->adjust($item, (float) $data['new_stock'], $request->user()?->id, $data['notes'] ?? null);

        return redirect()->route('stock.index')->with('success', 'Ajuste registrado com sucesso.');
    }

    private function movementQuery(
        int $movementItemId,
        ?string $movementType,
        ?string $movementFrom,
        ?string $movementTo,
    ): Builder {
        return StockMovement::query()
            ->with(['catalogItem:id,name,item_type', 'user:id,name'])
            ->when($movementItemId > 0, fn ($q) => $q->where('catalog_item_id', $movementItemId))
            ->when(in_array($movementType, ['entrada', 'saida', 'ajuste'], true), fn ($q) => $q->where('movement_type', $movementType))
            ->when($movementFrom !== null && $movementFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $movementFrom))
            ->when($movementTo !== null && $movementTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $movementTo));
    }
}
