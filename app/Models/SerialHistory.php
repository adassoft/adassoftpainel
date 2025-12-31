<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialHistory extends Model
{
    use HasFactory;

    protected $table = 'historico_seriais';
    public $timestamps = false;

    protected $fillable = [
        'empresa_codigo',
        'software_id',
        'serial_gerado',
        'data_geracao',
        'validade_licenca',
        'terminais_permitidos',
        'ativo',
        'observacoes'
    ];

    protected $casts = [
        'data_geracao' => 'datetime',
        'validade_licenca' => 'date',
        'ativo' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'empresa_codigo', 'codigo');
    }

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id');
    }
}
