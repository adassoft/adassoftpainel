<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanoRevenda extends Model
{
    protected $table = 'planos_revenda';
    public $timestamps = false;

    // Supondo PK id se existir, senao composite. 
    // Na tabela legacy não vi PK explícita no create script, mas assumiremos que o Eloquent precisa de ID ou tratamento especial.
    // Vamos assumir 'id' padrão por enquanto.

    protected $fillable = ['cnpj_revenda', 'plano_id', 'valor_venda', 'ativo'];

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }
}
