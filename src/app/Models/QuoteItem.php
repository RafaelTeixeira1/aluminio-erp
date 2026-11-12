<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'catalog_item_id',
        'item_name',
        'item_type',
        'quantity',
        'unit_price',
        'width_mm',
        'height_mm',
        'line_total',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'width_mm' => 'decimal:2',
            'height_mm' => 'decimal:2',
            'line_total' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Padrao Factory Method (Product): regra de calculo padrao, por unidade.
     * As subclasses em App\Models\QuoteItems sobrescrevem conforme a forma de venda do produto.
     */
    public function calculateTotal(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }

    /**
     * Valida os dados obrigatorios do item antes de ele entrar no orcamento.
     */
    public function validateForQuote(): void
    {
        if ((float) $this->quantity <= 0) {
            throw new DomainException('Informe uma quantidade maior que zero para o item "'.$this->item_name.'".');
        }
    }

    /**
     * Informacoes de medida guardadas junto ao item (por exemplo, area em m2 ou metragem de perfil).
     *
     * @return array<string, mixed>
     */
    public function measureMetadata(): array
    {
        return [];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
