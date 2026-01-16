<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbCategory extends Model
{
    use Concerns\HasSeo;

    protected $fillable = [
        'parent_id',
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

    public function parent()
    {
        return $this->belongsTo(KbCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(KbCategory::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function getFullNameAttribute()
    {
        return $this->parent ? "{$this->parent->name} > {$this->name}" : $this->name;
    }
}
