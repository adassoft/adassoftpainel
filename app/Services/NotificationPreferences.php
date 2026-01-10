<?php

namespace App\Services;

use App\Models\Configuration;

class NotificationPreferences
{
    protected array $preferences;

    public function __construct()
    {
        $config = Configuration::where('chave', 'notification_settings')->value('valor');
        $this->preferences = $config ? json_decode($config, true) : [];
    }

    public function shouldNotify(string $event, string $recipientType, string $channel): bool
    {
        // $recipientType = 'me' or 'customer'
        // $channel = 'email', 'sms', 'whatsapp'
        return in_array($channel, $this->preferences[$event][$recipientType] ?? []);
    }

    public function getDaysBeforeDue(): int
    {
        return (int) ($this->preferences['days_before_due']['days'] ?? 10);
    }
}
