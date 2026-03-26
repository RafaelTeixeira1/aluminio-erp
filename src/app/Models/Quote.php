<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quote_number',
        'client_id',
        'created_by_user_id',
        'status',
        'subtotal',
        'discount',
        'total',
        'valid_until',
        'payment_method',
        'notes',
        'item_quantification_notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function pieceDesigns(): HasMany
    {
        return $this->hasMany(PieceDesign::class);
    }

    public function designSketches(): HasMany
    {
        return $this->hasMany(DesignSketch::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
