<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(
        private readonly SequenceService $sequenceService
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, ?int $userId): Quote
    {
        return DB::transaction(function () use ($data, $userId): Quote {
            $quote = Quote::create([
                'client_id' => $data['client_id'],
                'created_by_user_id' => $userId,
                'status' => 'aberto',
                'subtotal' => $data['subtotal'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'total' => $data['total'] ?? 0,
                'valid_until' => $data['valid_until'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'item_quantification_notes' => $data['item_quantification_notes'] ?? null,
            ]);

            // Gera número sequencial do orçamento
            $quoteNumber = $this->sequenceService->generateNext(
                'QT_ORCAMENTO',
                'Quote',
                $quote->id
            );
            $quote->update(['quote_number' => $quoteNumber]);

            return $quote->fresh();
        });
    }

    public function expireOverdueQuotes(): int
    {
        return Quote::query()
            ->whereIn('status', ['aberto', 'aprovado'])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => 'expirado']);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function replaceItems(Quote $quote, array $items): Quote
    {
        return DB::transaction(function () use ($quote, $items) {
            $quote->items()->delete();

            foreach ($items as $payload) {
                $catalogItem = null;
                if (!empty($payload['catalog_item_id'])) {
                    $catalogItem = CatalogItem::query()->findOrFail($payload['catalog_item_id']);
                }

                $quantity = (float) ($payload['quantity'] ?? 0);
                $unitPrice = $catalogItem !== null
                    ? (float) $catalogItem->price
                    : (float) ($payload['unit_price'] ?? 0);
                $itemName = $catalogItem !== null
                    ? (string) $catalogItem->name
                    : (string) ($payload['item_name'] ?? '');
                $itemType = $catalogItem !== null
                    ? (string) $catalogItem->item_type
                    : (string) ($payload['item_type'] ?? 'produto');

                $quote->items()->create([
                    'catalog_item_id' => $catalogItem?->id,
                    'item_name' => $itemName,
                    'item_type' => $itemType,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'width_mm' => $payload['width_mm'] ?? null,
                    'height_mm' => $payload['height_mm'] ?? null,
                    'line_total' => $quantity * $unitPrice,
                    'metadata' => $payload['metadata'] ?? null,
                ]);
            }

            return $this->recalculate($quote->fresh());
        });
    }

    public function recalculate(Quote $quote): Quote
    {
        $subtotal = (float) $quote->items()->sum('line_total');
        $discount = max(0, min((float) $quote->discount, $subtotal));
        $total = $subtotal - $discount;

        $quote->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
        ]);

        return $quote->fresh(['items', 'designSketches', 'pieceDesigns', 'client']);
    }
}
