<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'download_id',
        'empresa',
        'nome',
        'email',
        'whatsapp',
        'ip_address',
        'converted',
    ];

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }
}
