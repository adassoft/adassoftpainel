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
        'plano_id',
        'asaas_payment_id',
        'external_reference',
        'status',
        'valor',
        'cnpj_revenda',
        'licenca_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Relaciona com tabela usuario (via Model User)
    }

    public function plan()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }
}
