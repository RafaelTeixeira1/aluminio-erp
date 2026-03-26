<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'created_by_user_id',
        'order_number',
        'status',
        'ordered_at',
        'expected_delivery_date',
        'payment_due_date',
        'received_at',
        'subtotal',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'date',
            'expected_delivery_date' => 'date',
            'payment_due_date' => 'date',
            'received_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function payable(): HasOne
    {
        return $this->hasOne(Payable::class);
    }
}
