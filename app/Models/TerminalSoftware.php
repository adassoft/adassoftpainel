<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminalSoftware extends Model
{
    use HasFactory;

    protected $table = 'terminais_software';
    public $timestamps = false;

    protected $fillable = [
        'terminal_codigo',
        'licenca_id',
        'ultima_atividade',
        'ativo',
        'data_vinculo',
        'instalacao_id',
        'ip_origem'
    ];

    protected $casts = [
        'ultima_atividade' => 'datetime',
        'data_vinculo' => 'datetime',
        'ativo' => 'boolean'
    ];

    public function terminal()
    {
        return $this->belongsTo(Terminal::class, 'terminal_codigo', 'CODIGO');
    }
}
