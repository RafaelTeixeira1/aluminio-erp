<?php

namespace App\Services\QuoteItems;

use App\Models\CatalogItem;
use App\Models\QuoteItem;
use App\Models\QuoteItems\AccessoryQuoteItem;

class AccessoryItemCreator extends QuoteItemCreator
{
    protected function criarItem(?CatalogItem $product): QuoteItem
    {
        return (new AccessoryQuoteItem())->useSaleUnit((string) ($product?->sale_unit ?? 'unidade'));
    }
}
