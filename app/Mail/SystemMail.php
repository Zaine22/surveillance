<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $subjectText;
    protected array $data;
    protected string $viewname;

    public function __construct(
        string $subjectText,
        array $data = [],
        string $viewname = 'emails.system'
    ) {
        $this->subjectText = $subjectText;
        $this->data = $data;
        $this->viewname = $viewname;
    }

    /**
     * Email subject
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText
        );
    }

    /**
     * Email content
     */
    public function content(): Content
    {
        return new Content(
            view: $this->viewname ?? 'emails.system',
            with: $this->data
        );
    }

    /**
     * Attachments
     */
    public function attachments(): array
    {
        return [];
    }
}
