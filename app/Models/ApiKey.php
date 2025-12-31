<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $table = 'api_keys';

    protected $fillable = [
        'software_id',
        'empresa_codigo',
        'label',
        'key_hash',
        'key_hint',
        'scopes',
        'status',
        'expires_at',
        'last_used_at',
        'use_count',
        'created_by',
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'use_count' => 'integer',
        'empresa_codigo' => 'integer',
        'software_id' => 'integer',
    ];

    public function software()
    {
        return $this->belongsTo(Software::class, 'software_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'empresa_codigo', 'codigo');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
