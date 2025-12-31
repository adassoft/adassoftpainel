<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginControl extends Model
{
    use HasFactory;

    protected $table = 'api_login_controle';
    public $timestamps = false; // Controlado manualmente via atualizado_em

    protected $fillable = [
        'email',
        'ip',
        'tentativas',
        'bloqueado_ate',
        'atualizado_em'
    ];

    protected $casts = [
        'bloqueado_ate' => 'datetime',
        'atualizado_em' => 'datetime'
    ];
}
