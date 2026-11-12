<?php

namespace App\States\Quote;

use App\Models\Quote;

/**
 * Padrao State: cada situacao do orcamento e uma classe que define
 * quais operacoes sao permitidas e para qual situacao o orcamento vai.
 *
 * O comportamento padrao recusa todas as operacoes. Cada estado concreto
 * sobrescreve apenas o que e permitido naquela situacao.
 */
abstract class QuoteState
{
    abstract public function status(): string;

    /**
     * Alteracao de dados e itens do orcamento.
     */
    public function assertCanEdit(Quote $quote): void
    {
        throw $this->refuse('alterado');
    }

    public function approve(Quote $quote): void
    {
        throw $this->refuse('aprovado');
    }

    public function reopen(Quote $quote): void
    {
        throw $this->refuse('reaberto');
    }

    public function cancel(Quote $quote): void
    {
        throw $this->refuse('cancelado');
    }

    public function expire(Quote $quote): void
    {
        throw $this->refuse('expirado');
    }

    public function assertCanConvert(Quote $quote): void
    {
        throw $this->refuse('convertido em venda');
    }

    public function convert(Quote $quote): void
    {
        $this->assertCanConvert($quote);
        $quote->changeState(new ConvertedQuoteState());
    }

    /**
     * Regra comum aos estados que permitem conversao: o orcamento precisa estar dentro da validade.
     */
    protected function assertWithinValidity(Quote $quote): void
    {
        if ($quote->valid_until !== null && $quote->valid_until->copy()->endOfDay()->isPast()) {
            throw new InvalidQuoteTransition('Orcamento vencido. Atualize a validade antes de converter em venda.');
        }
    }

    protected function refuse(string $action): InvalidQuoteTransition
    {
        return new InvalidQuoteTransition(
            sprintf('Orcamento com status "%s" nao pode ser %s.', $this->status(), $action)
        );
    }
}
