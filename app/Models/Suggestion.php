<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Added for user relationship
use App\Models\Software; // Added for software relationship
use App\Models\SuggestionVote; // Added for votes relationship

class Suggestion extends Model
{
    protected $fillable = ['title', 'description', 'status', 'user_id', 'software_id', 'votes_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function software()
    {
        return $this->belongsTo(Software::class);
    }

    public function votes()
    {
        return $this->hasMany(SuggestionVote::class);
    }
}
