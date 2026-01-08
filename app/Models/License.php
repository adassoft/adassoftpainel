<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $table = 'licencas_ativas';
    public $timestamps = false;

    protected $fillable = [
        'empresa_codigo',
        'cnpj_revenda',
        'software_id',
        'serial_atual',
        'data_criacao',
        'data_ativacao',
        'data_expiracao',
        'data_ultima_renovacao',
        'terminais_utilizados',
        'terminais_permitidos',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
        'data_ativacao' => 'datetime',
        'data_expiracao' => 'datetime',
        'data_ultima_renovacao' => 'datetime',
    ];

    protected $appends = ['software_imagem', 'nome_software', 'resumo_terminais'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'empresa_codigo', 'codigo');
    }

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id');
    }

    public function terminals()
    {
        return $this->belongsToMany(Terminal::class, 'terminais_software', 'licenca_id', 'terminal_codigo')
            ->withPivot(['ativo', 'ultima_atividade', 'data_vinculo']);
    }

    public function getSoftwareImagemAttribute()
    {
        return $this->software ? $this->software->imagem : '/img/placeholder_card.svg';
    }

    public function getNomeSoftwareAttribute()
    {
        return $this->software ? $this->software->nome_software : 'Software Desconhecido';
    }

    public function getResumoTerminaisAttribute()
    {
        return "{$this->terminais_utilizados} / {$this->terminais_permitidos} Terminais";
    }

    /**
     * Retorna a lista unificada de terminais e instalações pendentes,
     * replicando a lógica legado de 'shieldListarTerminaisLicenca'.
     */
    public function getMergedTerminalsAttribute()
    {
        // 1. Terminais oficiais vinculados
        $terminais = \Illuminate\Support\Facades\DB::table('terminais_software as ts')
            ->join('terminais as t', 'ts.terminal_codigo', '=', 't.CODIGO')
            ->leftJoin('licenca_instalacoes as li', function ($join) {
                $join->on('li.licenca_id', '=', 'ts.licenca_id')
                    ->on('li.mac_address', '=', 't.MAC');
            })
            ->where('ts.licenca_id', $this->id)
            ->select([
                'ts.terminal_codigo',
                'ts.ultima_atividade',
                'ts.ativo',
                'ts.data_vinculo',
                't.CODIGO as terminal_id',
                't.NOME_COMPUTADOR as nome_computador',
                't.MAC as mac_address',
                'li.instalacao_id',
                'li.ultimo_registro'
            ])
            ->orderByDesc('ts.ativo')
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('COALESCE(ts.ultima_atividade, li.ultimo_registro, ts.data_vinculo)'))
            ->get()
            ->map(function ($item) {
                $item->source = 'terminal';
                return (array) $item;
            })
            ->toArray();

        // 2. Instalações soltas (ex: pendentes ou apenas registradas via API mas sem terminal criado)
        $instalacoesExtras = \Illuminate\Support\Facades\DB::table('licenca_instalacoes as li')
            ->where('li.licenca_id', $this->id)
            ->whereNotExists(function ($query) {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('terminais as t')
                    ->join('terminais_software as ts', function ($join) {
                        $join->on('ts.terminal_codigo', '=', 't.CODIGO');
                    })
                    ->whereColumn('ts.licenca_id', 'li.licenca_id')
                    ->whereColumn('t.MAC', 'li.mac_address');
            })
            ->select([
                \Illuminate\Support\Facades\DB::raw('NULL as terminal_codigo'),
                \Illuminate\Support\Facades\DB::raw('NULL as ultima_atividade'),
                \Illuminate\Support\Facades\DB::raw('NULL as ativo'),
                \Illuminate\Support\Facades\DB::raw('NULL as data_vinculo'),
                \Illuminate\Support\Facades\DB::raw('NULL as terminal_id'),
                \Illuminate\Support\Facades\DB::raw('NULL as nome_computador'),
                'li.mac_address',
                'li.instalacao_id',
                'li.ultimo_registro'
            ])
            ->get()
            ->map(function ($item) {
                $item->source = 'installation';
                return (array) $item;
            })
            ->toArray();

        return array_merge($terminais, $instalacoesExtras);
    }

    public function recalculateUsage()
    {
        $utilizados = \Illuminate\Support\Facades\DB::table('terminais_software')
            ->where('licenca_id', $this->id)
            ->where('ativo', 1)
            ->count();

        // Considera também licenca_instalacoes se necessário (lógica legado do Admin)
        $instalacoes = \Illuminate\Support\Facades\DB::table('licenca_instalacoes')
            ->where('licenca_id', $this->id)
            ->count();

        $final = max($utilizados, $instalacoes);

        $this->update(['terminais_utilizados' => $final]);
    }
}
