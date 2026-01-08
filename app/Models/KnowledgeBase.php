<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'content',
        'tags',
        'is_public',
        'is_active',
        'video_url',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(KbCategory::class, 'category_id');
    }
}
