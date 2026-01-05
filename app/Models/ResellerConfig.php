<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerConfig extends Model
{
    use HasFactory;

    protected $table = 'revenda_config';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'nome_sistema',
        'slogan',
        'logo_path',
        'icone_path',
        'cor_primaria_gradient_start',
        'cor_primaria_gradient_end',
        'cor_acento',
        'cor_secundaria',
        'dominios',
        'ativo',
        'dados_pendentes',
        'status_aprovacao',
        'mensagem_rejeicao',
        'is_default',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'is_default' => 'boolean',
        'dados_pendentes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function history()
    {
        return $this->hasMany(ResellerConfigHistory::class, 'revenda_config_id');
    }
}
