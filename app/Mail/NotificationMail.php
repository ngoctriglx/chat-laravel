<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $setups;

    /**
     * Create a new message instance.
     */
    public function __construct($setups)
    {
        $this->setups = $setups;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                address: $this->setups['from_email'] ?? config('mail.from.address'),
                name: $this->setups['from_name'] ?? config('mail.from.name'),
            ),
            replyTo: [
                new Address(
                    address: $this->setups['reply_to'] ?? $this->setups['from_email'] ?? config('mail.from.address'),
                    name: $this->setups['from_name'] ?? config('mail.from.name'),
                ),
            ],
            subject: ($this->setups['subject'] ?? config('mail.subject')) . ' - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->setups['template'] ?? null,
            with: $this->setups['data'] ?? [],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return $this->setups['attachments'] ?? [];
    }
}
