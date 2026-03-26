<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Quote;
use App\Models\Sale;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly ReceivableService $receivableService,
        private readonly SequenceService $sequenceService,
        private readonly AuditLogService $auditLogService,
    )
    {
    }

    public function createFromQuote(Quote $quote, ?int $userId): Sale
    {
        return DB::transaction(function () use ($quote, $userId) {
            $quote->loadMissing('items', 'client');

            $alreadyConverted = Sale::query()
                ->where('quote_id', $quote->id)
                ->exists();

            if ($quote->status === 'convertido' || $alreadyConverted) {
                throw new DomainException('Este orcamento ja foi convertido em venda.');
            }

            $sale = Sale::create([
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'created_by_user_id' => $userId,
                'status' => 'pendente',
                'subtotal' => $quote->subtotal,
                'discount' => $quote->discount,
                'total' => $quote->total,
            ]);

            // Gera número sequencial da venda
            $saleNumber = $this->sequenceService->generateNext(
                'VD_VENDA',
                'Sale',
                $sale->id
            );
            $sale->update(['sale_number' => $saleNumber]);

            foreach ($quote->items as $item) {
                $sale->items()->create([
                    'catalog_item_id' => $item->catalog_item_id,
                    'item_name' => $item->item_name,
                    'item_type' => $item->item_type,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'metadata' => $item->metadata,
                ]);
            }

            $quote->update(['status' => 'convertido']);

            $this->auditLogService->record(
                action: 'quote.converted_to_sale',
                userId: $userId,
                entityType: Sale::class,
                entityId: $sale->id,
                payload: [
                    'quote_id' => $quote->id,
                    'total' => (float) $sale->total,
                ],
            );

            return $sale->fresh(['items', 'client']);
        });
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function replaceItems(Sale $sale, array $items): Sale
    {
        $this->assertEditable($sale);

        return DB::transaction(function () use ($sale, $items) {
            $sale->items()->delete();

            foreach ($items as $payload) {
                $catalogItem = null;
                if (!empty($payload['catalog_item_id'])) {
                    $catalogItem = CatalogItem::query()->findOrFail($payload['catalog_item_id']);
                }

                $quantity = (float) ($payload['quantity'] ?? 0);
                $unitPrice = (float) ($payload['unit_price'] ?? ($catalogItem?->price ?? 0));

                $sale->items()->create([
                    'catalog_item_id' => $catalogItem?->id,
                    'item_name' => (string) ($payload['item_name'] ?? $catalogItem?->name ?? ''),
                    'item_type' => (string) ($payload['item_type'] ?? $catalogItem?->item_type ?? 'produto'),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $quantity * $unitPrice,
                    'metadata' => $payload['metadata'] ?? null,
                ]);
            }

            return $this->recalculate($sale->fresh());
        });
    }

    public function recalculate(Sale $sale): Sale
    {
        $this->assertEditable($sale);

        $subtotal = (float) $sale->items()->sum('line_total');
        $discount = max(0, min((float) $sale->discount, $subtotal));
        $total = $subtotal - $discount;

        $sale->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
        ]);

        return $sale->fresh(['items', 'client', 'quote']);
    }

    /**
     * @param array<string, mixed> $financialOptions
     */
    public function confirmSale(Sale $sale, ?int $userId, array $financialOptions = []): Sale
    {
        $this->assertEditable($sale);

        return DB::transaction(function () use ($sale, $userId, $financialOptions) {
            $sale->loadMissing('items.catalogItem');

            foreach ($sale->items as $item) {
                if ($item->catalog_item_id === null) {
                    continue;
                }

                $catalogItem = $item->catalogItem;
                if ($catalogItem === null) {
                    throw new DomainException('Item de venda sem produto vinculado.');
                }

                $this->stockService->outputForSale(
                    item: $catalogItem,
                    quantity: (float) $item->quantity,
                    userId: $userId,
                    saleId: (int) $sale->id,
                );
            }

            $sale->update([
                'status' => 'confirmada',
                'confirmed_at' => now(),
            ]);

            $receivable = $this->receivableService->createFromSale($sale, $userId);

            $installments = max(1, (int) ($financialOptions['installments'] ?? 1));
            if ($installments > 1) {
                $firstDueDate = !empty($financialOptions['first_due_date'])
                    ? Carbon::parse((string) $financialOptions['first_due_date'])
                    : now();
                $intervalDays = max(1, (int) ($financialOptions['interval_days'] ?? 30));

                $this->receivableService->splitIntoInstallments(
                    receivable: $receivable,
                    installments: $installments,
                    firstDueDate: $firstDueDate,
                    intervalDays: $intervalDays,
                    userId: $userId,
                );
            }

            $this->auditLogService->record(
                action: 'sale.confirmed',
                userId: $userId,
                entityType: Sale::class,
                entityId: $sale->id,
                payload: [
                    'total' => (float) $sale->total,
                    'items_count' => $sale->items->count(),
                ],
            );

            return $sale->fresh(['items', 'client', 'quote']);
        });
    }

    private function assertEditable(Sale $sale): void
    {
        if ($sale->status === 'confirmada') {
            throw new DomainException('Venda concluida nao pode ser alterada.');
        }
    }
}
