<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class SubscriptionConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $plan,
        public string $billingCycle,   // 'mensual' | 'anual'
        public string $startDate,
        public float  $amount,
        public string $pdfPath,
        public string $iban,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Suscripción {$this->plan} activada – {$this->companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'Mail.subscriptionConfirmed'
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("factura_{$this->companyName}_{$this->startDate}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
