<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\States\Quote\ApprovedQuoteState;
use App\States\Quote\CancelledQuoteState;
use App\States\Quote\ConvertedQuoteState;
use App\States\Quote\ExpiredQuoteState;
use App\States\Quote\InvalidQuoteTransition;
use App\States\Quote\OpenQuoteState;
use App\States\Quote\QuoteState;
use App\States\Quote\UnknownQuoteState;

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

    /**
     * Padrao State: devolve o objeto que representa a situacao atual do orcamento.
     * O atributo status continua sendo o valor gravado no banco.
     */
    public function state(): QuoteState
    {
        return match ((string) $this->status) {
            'aberto' => new OpenQuoteState(),
            'aprovado' => new ApprovedQuoteState(),
            'convertido' => new ConvertedQuoteState(),
            'cancelado' => new CancelledQuoteState(),
            'expirado' => new ExpiredQuoteState(),
            default => new UnknownQuoteState((string) $this->status),
        };
    }

    /**
     * Chamado pelos estados concretos para trocar a situacao do orcamento.
     */
    public function changeState(QuoteState $state): void
    {
        $this->status = $state->status();
        $this->save();
    }

    /**
     * Leva o orcamento para a situacao pedida, desde que o estado atual permita.
     */
    public function transitionTo(string $status): void
    {
        if ($status === (string) $this->status) {
            return;
        }

        $state = $this->state();

        match ($status) {
            'aberto' => $state->reopen($this),
            'aprovado' => $state->approve($this),
            'cancelado' => $state->cancel($this),
            'expirado' => $state->expire($this),
            'convertido' => throw new InvalidQuoteTransition('Para marcar o orcamento como convertido, use a conversao em venda.'),
            default => throw new InvalidQuoteTransition('Status de orcamento invalido.'),
        };
    }
}
