<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Plano extends Model
{
    protected $table = 'planos';
    public $timestamps = false; // Se tiver, mude para true

    protected $fillable = ['software_id', 'nome_plano', 'valor', 'recorrencia', 'status'];

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id');
    }

    /**
     * Retorna a configuração específica deste plano para o revendedor logado.
     * Usamos o CNPJ do usuário logado para filtrar.
     */
    public function minhaConfig(): HasOne
    {
        // Nota: Auth::user() pode não estar disponível em todos os contextos (ex: jobs), 
        // mas em requisições HTTP do Filament estará.

        $cnpj = Auth::user()?->cnpj;

        return $this->hasOne(PlanoRevenda::class, 'plano_id')
            ->where('cnpj_revenda', $cnpj);
    }

    /**
     * Retorna o preço de venda para o contexto atual (Loja/Site).
     * Usa o serviço de Branding para identificar a revenda pelo domínio.
     */
    public function getPrecoFinalAttribute()
    {
        $cnpj = \App\Services\ResellerBranding::getCurrentCnpj();

        if (!$cnpj) {
            return $this->valor; // Fallback para preço base se não identificar revenda
        }

        // Busca configuração específica (usando query direta para evitar carregamento excessivo se não estiver em eager load dentro do loop poderia ser N+1, mas ok para poucos planos)
        // O ideal seria eager loading na Controller, mas aqui é um accessor de conveniência.
        $config = \App\Models\PlanoRevenda::where('plano_id', $this->id)
            ->where('cnpj_revenda', $cnpj)
            ->first();

        return ($config && $config->valor_venda > 0) ? $config->valor_venda : $this->valor;
    }

    /**
     * Define se o plano está visível na loja da revenda atual.
     * Padrão: FALSE (Precisa ativar no painel).
     */
    public function getIsAtivoRevendaAttribute()
    {
        $cnpj = \App\Services\ResellerBranding::getCurrentCnpj();

        if (!$cnpj || $cnpj === '00000000000100')
            return true;

        $config = \App\Models\PlanoRevenda::where('plano_id', $this->id)
            ->where('cnpj_revenda', $cnpj)
            ->first();

        return $config ? (bool) $config->ativo : false;
    }
}
