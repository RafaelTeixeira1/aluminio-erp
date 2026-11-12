<?php

namespace App\Models\QuoteItems;

use App\Models\QuoteItem;

/**
 * Acessorios e ferragens: vendidos por unidade ou por pacote.
 * O total e a quantidade multiplicada pelo preco da unidade ou do pacote.
 */
class AccessoryQuoteItem extends QuoteItem
{
    protected $table = 'quote_items';

    private string $saleUnit = 'unidade';

    public function useSaleUnit(string $saleUnit): static
    {
        $this->saleUnit = $saleUnit === 'pacote' ? 'pacote' : 'unidade';

        return $this;
    }

    public function measureMetadata(): array
    {
        return $this->saleUnit === 'pacote' ? ['sale_unit' => 'pacote'] : [];
    }
}
