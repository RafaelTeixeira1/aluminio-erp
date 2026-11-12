<?php

namespace App\Services\QuoteItems;

use App\Models\CatalogItem;
use App\Models\QuoteItem;
use App\Models\QuoteItems\GlassQuoteItem;

class GlassItemCreator extends QuoteItemCreator
{
    protected function criarItem(?CatalogItem $product): QuoteItem
    {
        return new GlassQuoteItem();
    }
}
