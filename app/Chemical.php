<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chemical extends Model
{
    protected $guarded = [];

    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }
    
    public function path()
    {
        return "chemicals/{$this->id}";
    }

    public function scopeShelf($query, $shelf)
    {
        $query->whereBetween('number', [100*$shelf, ($shelf*100)+99]);
    }
}
