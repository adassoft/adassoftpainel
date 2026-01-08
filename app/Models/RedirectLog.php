<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedirectLog extends Model
{
    protected $fillable = [
        'path',
        'hits',
        'last_accessed_at',
        'ip',
        'user_agent',
        'is_resolved',
        'is_ignored',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'is_resolved' => 'boolean',
        'is_ignored' => 'boolean',
    ];
}
