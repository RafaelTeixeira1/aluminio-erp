<?php

namespace App\States\Quote;

use App\Models\Quote;

class ApprovedQuoteState extends QuoteState
{
    public function status(): string
    {
        return 'aprovado';
    }

    public function assertCanEdit(Quote $quote): void
    {
    }

    public function reopen(Quote $quote): void
    {
        $quote->changeState(new OpenQuoteState());
    }

    public function cancel(Quote $quote): void
    {
        $quote->changeState(new CancelledQuoteState());
    }

    public function expire(Quote $quote): void
    {
        $quote->changeState(new ExpiredQuoteState());
    }

    public function assertCanConvert(Quote $quote): void
    {
        $this->assertWithinValidity($quote);
    }
}
