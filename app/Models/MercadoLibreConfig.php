<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoLibreConfig extends Model
{
    protected $fillable = [
        'company_id',
        'app_id',
        'secret_key',
        'redirect_uri',
        'access_token',
        'refresh_token',
        'expires_at',
        'ml_user_id',
        'ml_user_nickname',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'secret_key' => 'encrypted',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
