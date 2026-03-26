<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\StockMovement;
use App\Services\StockService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 30), 200);

        $items = CatalogItem::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate($perPage)
            ->through(function (CatalogItem $item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'item_type' => $item->item_type,
                    'stock' => $item->stock,
                    'stock_minimum' => $item->stock_minimum,
                    'status' => $item->stock_status,
                ];
            });

        return response()->json($items);
    }

    public function history(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 30), 200);

        $history = StockMovement::query()
            ->with(['catalogItem:id,name,item_type', 'user:id,name'])
            ->when($request->filled('catalog_item_id'), fn ($q) => $q->where('catalog_item_id', $request->integer('catalog_item_id')))
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($history);
    }

    public function entry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = CatalogItem::query()->findOrFail($data['catalog_item_id']);
        $movement = $this->stockService->entry($item, (float) $data['quantity'], $request->user()?->id, $data['notes'] ?? null);

        return response()->json($movement, 201);
    }

    public function adjust(Request $request): JsonResponse
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'new_stock' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = CatalogItem::query()->findOrFail($data['catalog_item_id']);
        $movement = $this->stockService->adjust($item, (float) $data['new_stock'], $request->user()?->id, $data['notes'] ?? null);

        return response()->json($movement, 201);
    }

    public function output(Request $request): JsonResponse
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = CatalogItem::query()->findOrFail($data['catalog_item_id']);

        try {
            $movement = $this->stockService->manualOutput($item, (float) $data['quantity'], $request->user()?->id, $data['notes'] ?? null);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($movement, 201);
    }
}
