<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLibrary extends Model
{
    use HasFactory;

    protected $table = 'user_library';

    protected $fillable = [
        'user_id',
        'download_id',
        'order_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function download()
    {
        return $this->belongsTo(Download::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
