<?php

namespace App\Models\QuoteItems;

use App\Models\QuoteItem;
use DomainException;

/**
 * Item de perfil de aluminio: vendido por barra de 6 metros.
 * A quantidade do item e o numero de barras, e o preco e o valor de uma barra.
 */
class ProfileQuoteItem extends QuoteItem
{
    public const BAR_LENGTH_MM = 6000;

    protected $table = 'quote_items';

    public function validateForQuote(): void
    {
        parent::validateForQuote();

        $quantity = (float) $this->quantity;
        if (abs($quantity - round($quantity)) > 0.0001) {
            throw new DomainException('O perfil "'.$this->item_name.'" e vendido por barra inteira de 6 metros. Informe a quantidade de barras.');
        }
    }

    public function calculateTotal(): float
    {
        return round(round((float) $this->quantity) * (float) $this->unit_price, 2);
    }

    public function totalLengthMeters(): float
    {
        return round((float) $this->quantity) * self::BAR_LENGTH_MM / 1000;
    }

    public function measureMetadata(): array
    {
        return [
            'sale_unit' => 'barra',
            'bar_length_mm' => self::BAR_LENGTH_MM,
            'total_length_m' => $this->totalLengthMeters(),
        ];
    }
}
