<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Software extends Model
{
    protected $table = 'softwares';
    public $timestamps = false; // Se quiser createdAt/updatedAt, mude para true

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $slug = \Illuminate\Support\Str::slug($model->nome_software);
                $count = static::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();
                $model->slug = $count ? "{$slug}-{$count}" : $slug;
            }
        });

        static::created(function ($software) {
            // Gera chave centralizada na tabela api_keys
            $rawKey = bin2hex(random_bytes(32));
            $hash = hash('sha256', $rawKey);
            $hint = substr($rawKey, -4);

            // Scopes padrão (tudo exceto offline)
            $scopes = [
                'emitir_token',
                'validar_serial',
                'status_licenca',
                'listar_terminais',
                'remover_terminal'
            ];

            \App\Models\ApiKey::create([
                'software_id' => $software->id,
                'label' => 'Chave Padrão (Gerada no Cadastro)',
                'key_hash' => $hash,
                'key_hint' => $hint,
                'scopes' => $scopes,
                'status' => 'ativo',
                'created_by' => auth()->id() ?? 1 // Fallback para admin se CLI
            ]);

            // Opcional: Salvar a raw key em algum lugar temporário ou log para o usuário ver?
            // Como é creation, se for Filament, o usuário não vê a chave se não mostrarmos.
            // Mas o Filament Resource geralmente redireciona. 
            // Para resolver isso, poderíamos usar session flash se estiver em contexto HTTP.
            if (session()) {
                session()->flash('generated_api_key', $rawKey);
            }
        });
    }

    protected $fillable = [
        'nome_software',
        'slug', // URL Amigável
        'codigo', // Novo
        'gtin', // Google Shopping
        'google_product_category', // Google Shopping
        'brand', // Google Shopping
        'descricao',
        'pagina_vendas_html', // Novo
        'categoria', // Novo
        'imagem_destaque', // Novo
        'linguagem',
        'plataforma',
        'imagem',
        'url_download',
        'arquivo_software', // Novo
        'tamanho_arquivo', // Novo
        'id_download_repo', // Novo
        'versao',
        'status',
        'disponivel_revenda',
        'api_key_hash',
        'api_key_hint',
        'api_key_gerada_em'
    ];

    protected $casts = [
        'api_key_gerada_em' => 'datetime',
        'data_cadastro' => 'datetime',
        'disponivel_revenda' => 'boolean',
    ];

    public function plans()
    {
        return $this->hasMany(Plano::class, 'software_id');
    }
}
