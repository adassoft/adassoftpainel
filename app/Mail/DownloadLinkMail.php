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
    public $nome;

    public function __construct(Download $download, string $link, ?string $nome = null)
    {
        $this->download = $download;
        $this->link = $link;
        // Extract First Name
        if ($nome) {
            $parts = explode(' ', trim($nome));
            $this->nome = $parts[0];
        } else {
            $this->nome = 'Visitante';
        }
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
            with: [
                'nome' => $this->nome,
                'download' => $this->download,
                'link' => $this->link,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
