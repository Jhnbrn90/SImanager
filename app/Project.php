<?php

namespace App;

use App\User;
use App\Compound;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function compounds()
    {
        return $this->hasMany(Compound::class);
    }
}
