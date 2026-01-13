<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;

class LogEmailSent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            /** @var \Symfony\Component\Mime\Email $message */
            $message = $event->message;

            $recipients = [];
            foreach ($message->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }

            \App\Models\MessageLog::create([
                'channel' => 'email',
                'recipient' => implode(', ', $recipients),
                'subject' => $message->getSubject(),
                'body' => $message->getTextBody() ?? $message->getHtmlBody(), // Capture Text first or HTML
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao salvar log de email: ' . $e->getMessage());
        }
    }
}
