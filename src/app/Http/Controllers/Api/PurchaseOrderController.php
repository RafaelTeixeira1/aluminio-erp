<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\PurchaseOrderService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $purchaseOrderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $orders = PurchaseOrder::query()
            ->with(['supplier', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->query('status')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->latest()
            ->paginate($perPage);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'ordered_at' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'payment_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:200'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $this->purchaseOrderService->create($data, $request->user()?->id);

        return response()->json($order, 201);
    }

    public function show(PurchaseOrder $compra): JsonResponse
    {
        $compra->load([
            'supplier',
            'createdBy',
            'items.catalogItem',
            'items.receipts.receivedBy',
            'payable',
        ]);

        return response()->json($compra);
    }

    public function receiveItem(Request $request, PurchaseOrder $compra, PurchaseOrderItem $item): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payable_document_number' => ['nullable', 'string', 'max:80'],
            'payable_due_date' => ['nullable', 'date'],
        ]);

        try {
            $order = $this->purchaseOrderService->receiveItem(
                purchaseOrder: $compra,
                purchaseOrderItem: $item,
                quantity: (float) $data['quantity'],
                userId: $request->user()?->id,
                unitCost: isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
                notes: $data['notes'] ?? null,
                payableDocumentNumber: $data['payable_document_number'] ?? null,
                payableDueDate: !empty($data['payable_due_date']) ? Carbon::parse((string) $data['payable_due_date']) : null,
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($order);
    }

    public function cancel(Request $request, PurchaseOrder $compra): JsonResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->purchaseOrderService->cancel($compra, $data['notes'] ?? null);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($order);
    }
}
