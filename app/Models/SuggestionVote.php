<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuggestionVote extends Model
{
    protected $fillable = ['user_id', 'suggestion_id'];
}
