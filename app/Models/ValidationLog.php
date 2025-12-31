<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidationLog extends Model
{
    use HasFactory;

    protected $table = 'log_validacoes';
    public $timestamps = false;

    protected $fillable = [
        'serial',
        'mac_address',
        'ip_address',
        'resultado',
        'data_validacao'
    ];

    protected $casts = [
        'data_validacao' => 'datetime',
        'resultado' => 'array'
    ];
}
