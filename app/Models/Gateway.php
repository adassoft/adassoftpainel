<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    protected $table = 'gateways';

    protected $fillable = [
        'gateway_name',
        'active',
        'producao',
        'client_id',
        'client_secret',
        'public_key',
        'access_token',
        'wallet_id',
        'min_recharge',
        'webhook_secret',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
