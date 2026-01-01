<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $fillable = ['title', 'content', 'tags', 'is_active'];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];
}
