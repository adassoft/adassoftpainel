<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'empresa';
    protected $primaryKey = 'codigo';
    public $timestamps = false;

    protected $fillable = [
        'cnpj',
        'razao',
        'endereco',
        'cidade',
        'bairro',
        'cep',
        'uf',
        'fone',
        'email',
        'data',
        'nterminais',
        'serial',
        'software_principal_id',
        'data_ultima_ativacao',
        'validade_licenca',
        'bloqueado',
        'cnpj_representante',
        'app_alerta_vencimento',
        'app_dias_alerta',
        'status',
        'saldo',
        'revenda_padrao',
        'asaas_access_token',
        'asaas_wallet_id',
    ];

    protected $casts = [
        'data' => 'datetime',
        'data_ultima_ativacao' => 'datetime',
        'validade_licenca' => 'date',
        'saldo' => 'decimal:2',
        'revenda_padrao' => 'boolean',
        'app_alerta_vencimento' => 'boolean',
    ];

    public function licenses()
    {
        return $this->hasMany(License::class, 'empresa_codigo', 'codigo');
    }

    public function creditHistories()
    {
        return $this->hasMany(CreditHistory::class, 'empresa_cnpj', 'cnpj');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'cnpj', 'cnpj');
    }
}
