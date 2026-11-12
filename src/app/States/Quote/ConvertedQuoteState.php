<?php

namespace App\States\Quote;

use App\Models\Quote;

/**
 * Estado final: o orcamento ja originou uma venda e nao aceita mais alteracoes.
 */
class ConvertedQuoteState extends QuoteState
{
    public function status(): string
    {
        return 'convertido';
    }

    public function assertCanConvert(Quote $quote): void
    {
        throw new InvalidQuoteTransition('Este orcamento ja foi convertido em venda.');
    }
}
