<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadVersion extends Model
{
    protected $guarded = [];

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }
}
