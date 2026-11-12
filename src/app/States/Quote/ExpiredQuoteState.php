<?php

namespace App\States\Quote;

/**
 * Estado final: o prazo de validade do orcamento terminou.
 */
class ExpiredQuoteState extends QuoteState
{
    public function status(): string
    {
        return 'expirado';
    }
}
