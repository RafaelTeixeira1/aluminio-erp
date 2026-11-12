<?php

namespace App\States\Quote;

use DomainException;

/**
 * Lancada quando uma operacao nao e permitida na situacao atual do orcamento.
 * Estende DomainException para ser tratada pelos controladores como erro de regra de negocio.
 */
class InvalidQuoteTransition extends DomainException
{
}
