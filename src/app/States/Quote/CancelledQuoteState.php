<?php

namespace App\States\Quote;

/**
 * Estado final: o orcamento foi cancelado e recusa todas as operacoes.
 */
class CancelledQuoteState extends QuoteState
{
    public function status(): string
    {
        return 'cancelado';
    }
}
