<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $guarded = [];

    public function owner()
    {
        return $this->bundle->user();
    }

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function compounds()
    {
        return $this->hasMany(Compound::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function path()
    {
        return '/projects/'.$this->id;
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
