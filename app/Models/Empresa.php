<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $primaryKey = 'codigo';

    public $timestamps = true;

    protected $fillable = [
        'razao', // Legacy name
        'nome_fantasia', // Created now
        'cnpj',
        'email',
        'fone',
        'cep',
        'endereco',
        'numero',
        'bairro',
        'cidade',
        'uf',
        'asaas_access_token',
        'asaas_wallet_id',
    ];
}
