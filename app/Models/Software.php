<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Software extends Model
{
    protected $table = 'softwares';
    public $timestamps = false;

    protected $fillable = ['nome_software', 'descricao', 'imagem', 'link_download', 'versao_atual'];

    public function plans()
    {
        return $this->hasMany(Plano::class, 'software_id');
    }
}
