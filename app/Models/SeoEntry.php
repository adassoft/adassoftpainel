<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEntry extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'url_path',
        'title',
        'description',
        'keywords',
        'focus_keyword',
        'robots',
        'canonical_url',
        'og_image',
        'json_ld',
    ];

    protected $casts = [
        'json_ld' => 'array',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
