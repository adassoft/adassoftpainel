<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditHistory extends Model
{
    use HasFactory;

    protected $table = 'historico_creditos';
    public $timestamps = false; // controlamos data_movimento manualmente ou via boot

    protected $fillable = [
        'empresa_cnpj',
        'usuario_id',
        'tipo',
        'valor',
        'descricao',
        'data_movimento'
    ];

    protected $casts = [
        'data_movimento' => 'datetime'
    ];
}
