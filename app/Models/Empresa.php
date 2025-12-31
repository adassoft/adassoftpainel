<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';

    // Assumindo que a PK seja 'id', mas a ligação com user é via 'cnpj'.
    // Importante verificar se 'empresa' tem timestamps
    public $timestamps = false;

    protected $fillable = [
        'nome_fantasia',
        'razao_social',
        'cnpj',
        'email',
        'fone',
        'asaas_access_token',
        'asaas_wallet_id',
        // Outros campos podem ser adicionados conforme descoberta
    ];
}
