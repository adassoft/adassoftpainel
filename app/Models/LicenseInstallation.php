<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseInstallation extends Model
{
    use HasFactory;

    protected $table = 'licenca_instalacoes';
    public $timestamps = false; // campos timestamp customizados

    protected $fillable = [
        'licenca_id',
        'serial',
        'instalacao_id',
        'mac_address',
        'hostname',
        'ultimo_ip',
        'primeiro_registro',
        'ultimo_registro'
    ];

    protected $casts = [
        'primeiro_registro' => 'datetime',
        'ultimo_registro' => 'datetime'
    ];
}
