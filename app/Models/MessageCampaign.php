<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'channels',
        'target_software_id',
        'target_download_id',
        'target_license_status',
        'target_type',
        'scheduled_at',
        'status',
        'processed_count',
        'total_targets',
    ];

    protected $casts = [
        'channels' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function software()
    {
        return $this->belongsTo(Software::class, 'target_software_id');
    }
    public function messageLogs()
    {
        return $this->hasMany(MessageLog::class, 'message_campaign_id');
    }
}
