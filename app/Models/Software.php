<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Software extends Model
{
    protected $table = 'softwares';
    public $timestamps = false;

    protected $fillable = [
        'nome_software',
        'descricao',
        'linguagem',
        'plataforma',
        'imagem',
        'url_download',
        'versao',
        'status',
        'api_key_hash',
        'api_key_hint',
        'api_key_gerada_em'
    ];

    public function plans()
    {
        return $this->hasMany(Plano::class, 'software_id');
    }
}
