<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'plano_id', // Legacy/Plans
        'asaas_payment_id', // Legacy/Plans (can map to external_id)
        'external_reference',
        'status',
        'valor', // Legacy
        'cnpj_revenda',
        'licenca_id',
        // Digital Products
        'total',
        'payment_method',
        'external_id',
        'payment_url',
        'paid_at',
        'recorrencia'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Relaciona com tabela usuario (via Model User)
    }

    public function plan()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
