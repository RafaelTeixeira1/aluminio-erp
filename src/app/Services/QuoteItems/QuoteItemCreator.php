<?php

namespace App\Services\QuoteItems;

use App\Models\CatalogItem;
use App\Models\QuoteItem;

/**
 * Padrao Factory Method (Creator).
 *
 * montarItem() define o processo comum: cria o item pelo metodo fabrica,
 * preenche os dados, valida e calcula o total. Cada criador concreto
 * decide apenas qual classe de item sera instanciada em criarItem().
 */
abstract class QuoteItemCreator
{
    /**
     * @param array<string, mixed> $data
     */
    public function montarItem(?CatalogItem $product, array $data): QuoteItem
    {
        $item = $this->criarItem($product);

        $item->fill([
            'catalog_item_id' => $product?->id,
            'item_name' => $product !== null ? (string) $product->name : (string) ($data['item_name'] ?? ''),
            'item_type' => $product !== null ? (string) $product->item_type : (string) ($data['item_type'] ?? 'produto'),
            'quantity' => (float) ($data['quantity'] ?? 0),
            'unit_price' => $product !== null ? (float) $product->price : (float) ($data['unit_price'] ?? 0),
            'width_mm' => $data['width_mm'] ?? null,
            'height_mm' => $data['height_mm'] ?? null,
        ]);

        $item->validateForQuote();
        $item->line_total = $item->calculateTotal();

        $metadata = array_merge(
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            $item->measureMetadata(),
        );
        $item->metadata = $metadata !== [] ? $metadata : null;

        return $item;
    }

    /**
     * Metodo fabrica: cada criador concreto escolhe a classe do item.
     */
    abstract protected function criarItem(?CatalogItem $product): QuoteItem;
}
