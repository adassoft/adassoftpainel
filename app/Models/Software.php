<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Software extends Model
{
    protected $table = 'softwares';
    public $timestamps = false;

    protected $fillable = [
        'nome_software',
        'codigo', // Novo
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
        'api_key_hash',
        'api_key_hint',
        'api_key_gerada_em'
    ];

    protected $casts = [
        'api_key_gerada_em' => 'datetime',
        'data_cadastro' => 'datetime',
    ];

    public function plans()
    {
        return $this->hasMany(Plano::class, 'software_id');
    }
}
