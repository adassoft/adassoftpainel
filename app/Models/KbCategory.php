<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbCategory extends Model
{
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
        return $this->hasMany(KnowledgeBase::class, 'category_id');
    }
}
