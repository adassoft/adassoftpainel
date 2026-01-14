<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $table = 'message_logs';

    protected $fillable = ['message_campaign_id', 'channel', 'recipient', 'subject', 'body', 'status', 'error_message', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(MessageCampaign::class, 'message_campaign_id');
    }
}
