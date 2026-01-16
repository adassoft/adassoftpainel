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
        'sort_order',
        'video_url',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    public function categories()
    {
        return $this->belongsToMany(KbCategory::class, 'kb_category_knowledge_base')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    // Helper to keep compatibility for now, returns the first category
    public function getCategoryAttribute()
    {
        return $this->categories->first();
    }
}
