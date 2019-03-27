<?php

namespace App;

use App\Traits\SearchableByStructure;
use Illuminate\Database\Eloquent\Model;

class Chemical extends Model
{
    use SearchableByStructure;

    protected $guarded = [];

    public function path()
    {
        return "chemicals/{$this->id}";
    }

    public function scopeShelf($query, $shelf)
    {
        $query->whereBetween('number', [100 * $shelf, ($shelf * 100) + 99]);
    }
}
