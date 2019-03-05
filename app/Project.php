<?php

namespace App;

use App\User;
use App\Compound;
use App\Reaction;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $guarded = [];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function compounds()
    {
        return $this->hasMany(Compound::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }
}
