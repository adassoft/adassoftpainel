<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Configuration extends Model
{
    /** @use HasFactory<\Database\Factories\ConfigurationFactory> */
    use HasFactory;

    protected $table = 'configuracoes';

    protected $fillable = ['chave', 'valor'];

    public $timestamps = false;
}
