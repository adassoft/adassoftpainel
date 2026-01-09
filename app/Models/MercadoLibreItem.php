<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoLibreItem extends Model
{
    protected $table = 'mercado_libre_items';

    protected $fillable = [
        'company_id',
        'ml_user_id',
        'ml_id',
        'title',
        'price',
        'currency_id',
        'available_quantity',
        'sold_quantity',
        'status',
        'permalink',
        'thumbnail',
        'download_id',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'price' => 'decimal:2',
        'available_quantity' => 'integer',
        'sold_quantity' => 'integer',
    ];

    public function download()
    {
        // Relacionamento com o produto local
        return $this->belongsTo(Download::class, 'download_id');
    }
}
