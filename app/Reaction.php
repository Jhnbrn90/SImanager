<?php

namespace App;

use App\Compound;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);        
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function compounds()
    {
        return $this->belongsToMany(Compound::class)->withPivot('type');
    }

    public function addStartingMaterial(Compound $compound)
    {
        $this->compounds()->attach($compound, ['type' => 'starting_material']);
        return $this;
    }

    public function getStartingMaterialsAttribute()
    {
        return $this->compounds()->wherePivot('type', 'starting_material')->get();
    }

    public function addReagent(Compound $compound)
    {
        $this->compounds()->attach($compound, ['type' => 'reagent']);
        return $this;
    }

    public function getReagentsAttribute()
    {
        return $this->compounds()->wherePivot('type', 'reagent')->get();
    }

    public function addProduct(Compound $compound)
    {
        $this->compounds()->attach($compound, ['type' => 'product']);
        return $this;
    }

    public function getProductsAttribute()
    {
        return $this->compounds()->wherePivot('type', 'product')->get();
    }

    public function path()
    {
        return '/reactions/' . $this->id;
    }

    public function nextProductLabel()
    {
        $letters = range('a', 'z');
        $addition = $letters[$this->products->count()];

        return "{$this->label}{$addition}";
    }
}
