<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerConfigHistory extends Model
{
    use HasFactory;

    protected $table = 'revenda_config_historico';
    public $timestamps = false;

    protected $fillable = [
        'revenda_config_id',
        'acao',
        'mensagem',
        'admin_id',
        'data_registro',
    ];

    protected $casts = [
        'data_registro' => 'datetime',
    ];

    public function config()
    {
        return $this->belongsTo(ResellerConfig::class, 'revenda_config_id');
    }
}
