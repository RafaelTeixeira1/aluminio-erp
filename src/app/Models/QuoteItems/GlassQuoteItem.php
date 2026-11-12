<?php

namespace App\Models\QuoteItems;

use App\Models\QuoteItem;
use DomainException;

/**
 * Item de vidro: vendido por metro quadrado.
 * A quantidade e o numero de pecas, as medidas sao informadas em milimetros
 * e o preco e o valor do m2 (que ja varia conforme espessura e cor do produto).
 */
class GlassQuoteItem extends QuoteItem
{
    protected $table = 'quote_items';

    public function validateForQuote(): void
    {
        parent::validateForQuote();

        if ((float) $this->width_mm <= 0 || (float) $this->height_mm <= 0) {
            throw new DomainException('Informe a largura e a altura em milimetros do vidro "'.$this->item_name.'".');
        }
    }

    public function areaPerPieceM2(): float
    {
        return ((float) $this->width_mm * (float) $this->height_mm) / 1_000_000;
    }

    public function totalAreaM2(): float
    {
        return round($this->areaPerPieceM2() * (float) $this->quantity, 4);
    }

    public function calculateTotal(): float
    {
        return round($this->areaPerPieceM2() * (float) $this->quantity * (float) $this->unit_price, 2);
    }

    public function measureMetadata(): array
    {
        return [
            'sale_unit' => 'm2',
            'area_m2' => $this->totalAreaM2(),
        ];
    }
}
