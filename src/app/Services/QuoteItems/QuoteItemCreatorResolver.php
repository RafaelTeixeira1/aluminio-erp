<?php

namespace App\Services\QuoteItems;

use App\Models\CatalogItem;

/**
 * Escolhe o criador de acordo com a unidade de venda do produto.
 * Itens sem produto do catalogo sao tratados como venda por unidade.
 */
class QuoteItemCreatorResolver
{
    public function forProduct(?CatalogItem $product): QuoteItemCreator
    {
        return match ((string) ($product?->sale_unit ?? 'unidade')) {
            'barra' => new ProfileItemCreator(),
            'm2' => new GlassItemCreator(),
            default => new AccessoryItemCreator(),
        };
    }
}
