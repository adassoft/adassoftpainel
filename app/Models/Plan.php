<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planos';
    public $timestamps = false; // Tabela 'planos' parece nao ter timestamps padrao

    protected $fillable = [
        'nome_plano',
        'software_id',
        'recorrencia',
        'valor',
        'status',
        'data_cadastro',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_cadastro' => 'datetime',
    ];

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id');
    }
}
