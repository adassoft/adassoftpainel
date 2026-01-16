<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbCategory extends Model
{
    use Concerns\HasSeo;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function articles()
    {
        return $this->belongsToMany(KnowledgeBase::class, 'kb_category_knowledge_base')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
