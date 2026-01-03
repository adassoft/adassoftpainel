<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'download_id',
        'product_name',
        'price'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function download()
    {
        return $this->belongsTo(Download::class, 'download_id');
    }
}
