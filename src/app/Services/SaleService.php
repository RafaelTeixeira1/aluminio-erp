<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Quote;
use App\Models\Sale;
use DomainException;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly SequenceService $sequenceService,
    )
    {
    }

    /**
     * Cria a venda pendente e seus itens a partir dos itens do orcamento.
     * Usado pela SaleFacade, que coordena o restante da conversao.
     */
    public function createPendingFromQuote(Quote $quote, ?int $userId): Sale
    {
        $quote->loadMissing('items');

        $sale = Sale::create([
            'client_id' => $quote->client_id,
            'quote_id' => $quote->id,
            'created_by_user_id' => $userId,
            'status' => 'pendente',
            'subtotal' => $quote->subtotal,
            'discount' => $quote->discount,
            'total' => $quote->total,
        ]);

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

        return $sale;
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

    public function assertEditable(Sale $sale): void
    {
        if ($sale->status === 'confirmada') {
            throw new DomainException('Venda concluida nao pode ser alterada.');
        }

        if ($sale->status === 'cancelada') {
            throw new DomainException('Venda cancelada nao pode ser alterada.');
        }
    }
}
