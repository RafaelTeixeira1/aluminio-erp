<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Quote;
use App\Services\QuoteItems\QuoteItemCreatorResolver;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(
        private readonly SequenceService $sequenceService,
        private readonly QuoteItemCreatorResolver $creatorResolver,
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
     * Substitui os itens do orcamento. Cada item e montado pelo criador
     * correspondente a unidade de venda do produto (Factory Method), e a
     * alteracao so acontece se a situacao atual do orcamento permitir (State).
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function replaceItems(Quote $quote, array $items): Quote
    {
        return DB::transaction(function () use ($quote, $items) {
            $quote->state()->assertCanEdit($quote);

            $quote->items()->delete();

            foreach ($items as $payload) {
                $catalogItem = null;
                if (!empty($payload['catalog_item_id'])) {
                    $catalogItem = CatalogItem::query()->findOrFail($payload['catalog_item_id']);
                }

                $item = $this->creatorResolver
                    ->forProduct($catalogItem)
                    ->montarItem($catalogItem, $payload);

                $quote->items()->save($item);
            }

            return $this->recalculate($quote->fresh());
        });
    }

    /**
     * Cria um orcamento aberto e o leva a situacao pedida, se houver.
     *
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>>|null $items
     */
    public function createWithItems(array $attributes, ?array $items, ?int $userId): Quote
    {
        return DB::transaction(function () use ($attributes, $items, $userId): Quote {
            $targetStatus = $attributes['status'] ?? null;
            unset($attributes['status']);

            $quote = Quote::query()->create([
                ...$attributes,
                'status' => 'aberto',
                'discount' => $attributes['discount'] ?? 0,
                'created_by_user_id' => $userId,
            ]);

            $quote = $items !== null && $items !== []
                ? $this->replaceItems($quote, $items)
                : $this->recalculate($quote);

            if (is_string($targetStatus) && $targetStatus !== '') {
                $quote->transitionTo($targetStatus);
            }

            return $quote->fresh(['items', 'designSketches', 'pieceDesigns', 'client']);
        });
    }

    /**
     * Atualiza dados e itens do orcamento e, por ultimo, aplica a mudanca de situacao.
     * Orcamentos convertidos, cancelados ou expirados nao aceitam alteracoes.
     *
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>>|null $items
     */
    public function update(Quote $quote, array $attributes, ?array $items = null): Quote
    {
        return DB::transaction(function () use ($quote, $attributes, $items): Quote {
            $quote->state()->assertCanEdit($quote);

            $targetStatus = $attributes['status'] ?? null;
            unset($attributes['status']);

            $quote->update($attributes);

            $quote = $items !== null && $items !== []
                ? $this->replaceItems($quote, $items)
                : $this->recalculate($quote->fresh());

            if (is_string($targetStatus) && $targetStatus !== '') {
                $quote->transitionTo($targetStatus);
            }

            return $quote->fresh(['items', 'designSketches', 'pieceDesigns', 'client']);
        });
    }

    /**
     * Valida se o orcamento pode ser convertido e o marca como convertido (usado pela SaleFacade).
     */
    public function assertCanConvert(Quote $quote): void
    {
        $quote->state()->assertCanConvert($quote);
    }

    public function markAsConverted(Quote $quote): void
    {
        $quote->state()->convert($quote);
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
