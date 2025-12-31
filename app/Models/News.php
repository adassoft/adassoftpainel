<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'noticias';

    protected $fillable = [
        'software_id',
        'titulo',
        'conteudo',
        'link_acao',
        'prioridade',
        'ativa',
        'publico',
        'tipo',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id');
    }

    // Scopes para facilitar filtragem
    public function scopeActive($query)
    {
        return $query->where('ativa', true);
    }

    public function scopeForReseller($query)
    {
        // Revenda vê 'revenda' e 'todos'
        return $query->active()->whereIn('publico', ['revenda', 'todos']);
    }

    public function scopeForClient($query)
    {
        // Cliente vê apenas 'todos'
        return $query->active()->where('publico', 'todos');
    }
}
