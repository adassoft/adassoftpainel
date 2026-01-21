<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Software extends Model
{
    use \App\Models\Concerns\HasSeo;

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
        'id_update_repo', // Novo (Repo de Updates)
        'versao',
        'status',
        'disponivel_revenda',
        'api_key_hash',
        'api_key_hint',
        'api_key_gerada_em',
        'faq',
        'galeria', // Galeria de Imagens
    ];

    protected $casts = [
        'api_key_gerada_em' => 'datetime',
        'data_cadastro' => 'datetime',
        'disponivel_revenda' => 'boolean',
        'faq' => 'array',
        'galeria' => 'array',
    ];

    public function plans()
    {
        return $this->hasMany(Plano::class, 'software_id');
    }

    public function downloadRepository()
    {
        return $this->belongsTo(Download::class, 'id_download_repo');
    }

    public function updateRepository()
    {
        return $this->belongsTo(Download::class, 'id_update_repo');
    }

    public function getJsonLdAttribute()
    {
        $url = url('/produto/' . $this->slug);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->nome_software,
            'description' => $this->descricao,
            'image' => $this->imagem && !str_starts_with($this->imagem, 'http') ? asset('storage/' . $this->imagem) : $this->imagem,
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->brand ?? 'Adassoft'
            ],
            'offers' => [],
        ];

        if ($this->gtin)
            $schema['gtin'] = $this->gtin;
        if ($this->categoria)
            $schema['category'] = $this->categoria;

        // Add Offers (Plans)
        if ($this->plans->count() > 0) {
            foreach ($this->plans as $plan) {
                $preco = $plan->preco_venda;
                $schema['offers'][] = [
                    '@type' => 'Offer',
                    'name' => ($plan->nome ?? 'Plano') . ' (' . ($plan->recorrencia ?? 1) . ' Meses)',
                    'price' => number_format($preco, 2, '.', ''),
                    'priceCurrency' => 'BRL',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $url . '#planos'
                ];
            }
        }

        // FAQ Schema
        $faqSchema = null;
        if (!empty($this->faq) && is_array($this->faq)) {
            $faqQuestions = [];
            foreach ($this->faq as $item) {
                if (!empty($item['question']) && !empty($item['answer'])) {
                    $faqQuestions[] = [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($item['answer'], '<a><p><b><strong><ul><ol><li><br>')
                        ]
                    ];
                }
            }
            if (!empty($faqQuestions)) {
                $faqSchema = [
                    '@type' => 'FAQPage',
                    'mainEntity' => $faqQuestions
                ];
            }
        }

        if ($faqSchema) {
            $graph = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    $schema,
                    $faqSchema
                ]
            ];
            unset($graph['@graph'][0]['@context']);
            return json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
