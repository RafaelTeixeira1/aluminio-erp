<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private readonly Quote $quote)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Orçamento #{$this->quote->id} - SD Aluminios",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote',
            with: [
                'quote' => $this->quote,
                'clientName' => $this->quote->client?->name,
                'contactEmail' => config('app.company_contact_email'),
                'phone' => config('app.company_phone'),
                'whatsapp' => config('app.company_whatsapp'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath(storage_path('app/temp/quote-'.$this->quote->id.'.pdf'))
                ->as('orcamento-'.$this->quote->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
