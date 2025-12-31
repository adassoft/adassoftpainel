<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenLog extends Model
{
    use HasFactory;

    protected $table = 'log_token_emissoes';
    public $timestamps = false;

    protected $fillable = [
        'email',
        'empresa_codigo',
        'software_id',
        'instalacao_id',
        'ip',
        'sucesso',
        'motivo',
        'criado_em'
    ];

    protected $casts = [
        'criado_em' => 'datetime',
        'sucesso' => 'boolean'
    ];
}
