<?php

namespace App\States\Quote;

use App\Models\Quote;

class OpenQuoteState extends QuoteState
{
    public function status(): string
    {
        return 'aberto';
    }

    public function assertCanEdit(Quote $quote): void
    {
    }

    public function approve(Quote $quote): void
    {
        $quote->changeState(new ApprovedQuoteState());
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
