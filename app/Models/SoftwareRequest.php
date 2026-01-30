<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'project_name',
        'project_type',
        'budget_range',
        'deadline',
        'description',
        'features_list',
        'status',
        'admin_notes'
    ];
    //
}
