<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'installment_number',
        'installment_count',
        'client_id',
        'created_by_user_id',
        'settled_by_user_id',
        'status',
        'amount_total',
        'amount_paid',
        'balance',
        'due_date',
        'settled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'installment_number' => 'integer',
            'installment_count' => 'integer',
            'due_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }
}