<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Payable;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly SequenceService $sequenceService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, ?int $userId): PurchaseOrder
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = $data['items'];

        return DB::transaction(function () use ($data, $items, $userId): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::query()->create([
                'supplier_id' => (int) $data['supplier_id'],
                'created_by_user_id' => $userId,
                'status' => 'aberto',
                'ordered_at' => $data['ordered_at'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'payment_due_date' => $data['payment_due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $rows = [];
            foreach ($items as $item) {
                $quantity = (float) $item['quantity_ordered'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);

                if ($quantity <= 0) {
                    throw new DomainException('Quantidade do item deve ser maior que zero.');
                }

                $catalogItem = null;
                if (!empty($item['catalog_item_id'])) {
                    $catalogItem = CatalogItem::query()->findOrFail((int) $item['catalog_item_id']);
                }

                $rows[] = [
                    'catalog_item_id' => $catalogItem?->id,
                    'item_name' => $catalogItem?->name ?? (string) ($item['item_name'] ?? ''),
                    'quantity_ordered' => $quantity,
                    'quantity_received' => 0,
                    'unit_cost' => $unitCost,
                    'line_total' => $quantity * $unitCost,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $purchaseOrder->items()->createMany($rows);
            $this->recalculateTotals($purchaseOrder);

            // Gera número sequencial da compra
            $orderNumber = $this->sequenceService->generateNext(
                'PO_COMPRA',
                'PurchaseOrder',
                $purchaseOrder->id
            );
            $purchaseOrder->update(['order_number' => $orderNumber]);

            $this->auditLogService->record(
                action: 'purchase_order.created',
                userId: $userId,
                entityType: PurchaseOrder::class,
                entityId: $purchaseOrder->id,
                payload: [
                    'supplier_id' => (int) $purchaseOrder->supplier_id,
                    'total' => (float) $purchaseOrder->total,
                    'items_count' => count($items),
                ],
            );

            return $purchaseOrder->fresh(['supplier', 'items.catalogItem', 'createdBy']);
        });
    }

    public function receiveItem(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderItem $purchaseOrderItem,
        float $quantity,
        ?int $userId,
        ?float $unitCost = null,
        ?string $notes = null,
        ?string $payableDocumentNumber = null,
        ?Carbon $payableDueDate = null,
    ): PurchaseOrder {
        if (!in_array($purchaseOrder->status, ['aberto', 'parcial'], true)) {
            throw new DomainException('Compra nao permite recebimento nesse status.');
        }

        if ($purchaseOrderItem->purchase_order_id !== $purchaseOrder->id) {
            throw new DomainException('Item nao pertence a compra informada.');
        }

        if ($quantity <= 0) {
            throw new DomainException('Quantidade de recebimento deve ser maior que zero.');
        }

        return DB::transaction(function () use ($purchaseOrder, $purchaseOrderItem, $quantity, $userId, $unitCost, $notes, $payableDocumentNumber, $payableDueDate): PurchaseOrder {
            $order = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            $item = PurchaseOrderItem::query()->whereKey($purchaseOrderItem->id)->lockForUpdate()->firstOrFail();

            $remaining = (float) $item->quantity_ordered - (float) $item->quantity_received;
            if ($quantity > $remaining + 0.000001) {
                throw new DomainException('Quantidade recebida maior que o saldo pendente do item.');
            }

            if ($item->catalog_item_id === null) {
                throw new DomainException('Item sem produto vinculado nao pode gerar entrada automatica em estoque.');
            }

            $effectiveUnitCost = $unitCost ?? (float) $item->unit_cost;
            $item->update([
                'quantity_received' => (float) $item->quantity_received + $quantity,
                'unit_cost' => $effectiveUnitCost,
                'line_total' => (float) $item->quantity_ordered * $effectiveUnitCost,
            ]);

            $item->receipts()->create([
                'received_by_user_id' => $userId,
                'quantity' => $quantity,
                'unit_cost' => $effectiveUnitCost,
                'notes' => $notes,
                'received_at' => now(),
            ]);

            $catalogItem = CatalogItem::query()->findOrFail($item->catalog_item_id);
            $this->stockService->entry(
                item: $catalogItem,
                quantity: $quantity,
                userId: $userId,
                notes: $notes ?: 'Entrada por compra '.($order->order_number ?? '#'.$order->id),
                originType: 'compra',
                originId: $order->id,
            );

            $this->recalculateTotals($order);
            $this->syncStatus($order);
            $this->ensurePayableWhenFullyReceived($order, $payableDocumentNumber, $payableDueDate);

            $this->auditLogService->record(
                action: 'purchase_order.item_received',
                userId: $userId,
                entityType: PurchaseOrder::class,
                entityId: $order->id,
                payload: [
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_cost' => $effectiveUnitCost,
                    'status' => $order->status,
                ],
            );

            return $order->fresh(['supplier', 'items.catalogItem', 'items.receipts', 'payable']);
        });
    }

    public function cancel(PurchaseOrder $purchaseOrder, ?string $notes = null): PurchaseOrder
    {
        if ($purchaseOrder->status === 'recebido') {
            throw new DomainException('Compra recebida nao pode ser cancelada.');
        }

        $purchaseOrder->update([
            'status' => 'cancelado',
            'notes' => $notes !== null && trim($notes) !== '' ? $notes : $purchaseOrder->notes,
        ]);

        $this->auditLogService->record(
            action: 'purchase_order.canceled',
            userId: $purchaseOrder->created_by_user_id,
            entityType: PurchaseOrder::class,
            entityId: $purchaseOrder->id,
            payload: ['status' => 'cancelado'],
        );

        return $purchaseOrder->fresh(['supplier', 'items.catalogItem', 'payable']);
    }

    private function recalculateTotals(PurchaseOrder $purchaseOrder): void
    {
        $subtotal = (float) $purchaseOrder->items()->sum('line_total');

        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);
    }

    private function syncStatus(PurchaseOrder $purchaseOrder): void
    {
        $items = $purchaseOrder->items()->get(['quantity_ordered', 'quantity_received']);
        $ordered = (float) $items->sum('quantity_ordered');
        $received = (float) $items->sum('quantity_received');

        $newStatus = 'aberto';
        $receivedAt = null;

        if ($received > 0.000001) {
            $newStatus = $received + 0.000001 >= $ordered ? 'recebido' : 'parcial';
        }

        if ($newStatus === 'recebido') {
            $receivedAt = now();
        }

        $purchaseOrder->update([
            'status' => $newStatus,
            'received_at' => $receivedAt,
        ]);
    }

    private function ensurePayableWhenFullyReceived(PurchaseOrder $purchaseOrder, ?string $documentNumber, ?Carbon $dueDate): void
    {
        if ($purchaseOrder->status !== 'recebido') {
            return;
        }

        $exists = Payable::query()->where('purchase_order_id', $purchaseOrder->id)->exists();
        if ($exists) {
            return;
        }

        $supplier = $purchaseOrder->supplier()->first();

        Payable::query()->create([
            'vendor_name' => (string) ($supplier?->name ?? 'Fornecedor #'.$purchaseOrder->supplier_id),
            'supplier_id' => $purchaseOrder->supplier_id,
            'purchase_order_id' => $purchaseOrder->id,
            'description' => 'Compra '.($purchaseOrder->order_number ?? '#'.$purchaseOrder->id),
            'category' => 'compra',
            'document_number' => $documentNumber ?? ($purchaseOrder->order_number ?? null),
            'created_by_user_id' => $purchaseOrder->created_by_user_id,
            'settled_by_user_id' => null,
            'status' => 'aberto',
            'amount_total' => (float) $purchaseOrder->total,
            'amount_paid' => 0,
            'balance' => (float) $purchaseOrder->total,
            'due_date' => $dueDate?->toDateString() ?? $purchaseOrder->payment_due_date,
            'paid_at' => null,
            'notes' => 'Gerado automaticamente a partir da compra '.($purchaseOrder->order_number ?? '#'.$purchaseOrder->id),
        ]);
    }
}
