<?php

namespace App\States\Quote;

/**
 * Usado para registros antigos com um status fora da lista conhecida.
 * Mantem o comportamento padrao, recusando todas as operacoes.
 */
class UnknownQuoteState extends QuoteState
{
    public function __construct(private readonly string $status)
    {
    }

    public function status(): string
    {
        return $this->status;
    }
}
