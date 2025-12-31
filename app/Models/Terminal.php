<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terminal extends Model
{
    use HasFactory;

    protected $table = 'terminais';
    protected $primaryKey = 'CODIGO';
    public $timestamps = false;

    protected $fillable = [
        'FK_EMPRESA',
        'MAC',
        'NOME_COMPUTADOR'
    ];
}
