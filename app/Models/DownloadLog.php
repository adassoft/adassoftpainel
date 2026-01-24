<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadLog extends Model
{
    use HasFactory;

    protected $table = 'download_logs';

    protected $fillable = [
        'download_id',
        'version_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
    ];

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }

    public function version()
    {
        return $this->belongsTo(DownloadVersion::class, 'version_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
