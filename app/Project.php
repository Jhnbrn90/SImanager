<?php

namespace App;

use App\User;
use App\Bundle;
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

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function path()
    {
        return '/projects/' . $this->id;
    }

    public function moveTo(Bundle $bundle)
    {
        $this->update(['bundle_id' => $bundle->id]);
    }

    public function isEmpty()
    {
        return $this->compounds()->count() == 0;
    }
}
