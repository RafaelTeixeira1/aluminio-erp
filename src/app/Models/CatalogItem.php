<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'image_url',
        'effective_weight_per_meter_kg',
    ];

    protected $fillable = [
        'name',
        'category_id',
        'item_type',
        'price',
        'stock',
        'stock_minimum',
        'weight_per_meter_kg',
        'material',
        'finish',
        'thickness_mm',
        'standard_width_mm',
        'standard_height_mm',
        'brand',
        'product_line',
        'technical_notes',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'decimal:3',
            'stock_minimum' => 'decimal:3',
            'weight_per_meter_kg' => 'decimal:3',
            'thickness_mm' => 'decimal:3',
            'standard_width_mm' => 'decimal:2',
            'standard_height_mm' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CatalogItemImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(CatalogItemImage::class)->where('is_primary', true)->latestOfMany('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, ?string $itemType): Builder
    {
        if ($itemType === null) {
            return $query;
        }

        return $query->where('item_type', $itemType);
    }

    public function getStockStatusAttribute(): string
    {
        return (float) $this->stock <= (float) $this->stock_minimum ? 'baixo' : 'normal';
    }

    public function getImageUrlAttribute(): ?string
    {
        $galleryPath = $this->primaryImage?->image_path;
        if (is_string($galleryPath) && trim($galleryPath) !== '') {
            return asset($galleryPath);
        }

        if (!is_string($this->image_path) || trim($this->image_path) === '') {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return asset($this->image_path);
    }

    public function getEffectiveWeightPerMeterKgAttribute(): ?float
    {
        if (is_numeric($this->weight_per_meter_kg)) {
            return (float) $this->weight_per_meter_kg;
        }

        $name = (string) $this->name;
        if (preg_match('/kg\/m\s*(\d+[\.,]\d{2,3})/iu', $name, $matches) === 1) {
            $normalized = str_replace(',', '.', $matches[1]);
            if (is_numeric($normalized)) {
                return (float) $normalized;
            }
        }

        return null;
    }
}
