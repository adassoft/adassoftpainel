<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Download;

class DownloadLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public $download;
    public $link;

    public function __construct(Download $download, string $link)
    {
        $this->download = $download;
        $this->link = $link;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu Link de Download: ' . $this->download->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.download-link',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
