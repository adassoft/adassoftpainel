<?php

namespace App\Models\Concerns;

use App\Models\SeoEntry;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoEntry::class, 'model');
    }

    /**
     * Helper para pegar título, fallbackando para o atributo 'nome' ou 'titulo' do model.
     */
    public function getSeoTitleAttribute(): string
    {
        return $this->seo?->title
            ?? $this->nome_software
            ?? $this->titulo
            ?? $this->name
            ?? config('app.name');
    }

    public function getSeoDescriptionAttribute(): string
    {
        // Tenta pegar do SEO, se não, limita a descrição/conteúdo do model
        if ($this->seo?->description) {
            return $this->seo->description;
        }

        $content = $this->descricao ?? $this->conteudo ?? $this->description ?? '';
        return \Illuminate\Support\Str::limit(strip_tags($content), 160);
    }
}
