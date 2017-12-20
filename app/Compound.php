<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Compound extends Model
{

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function path()
    {
        return "/compounds/{$this->id}";
    }

    public function getpathToMolfileAttribute()
    {
        return storage_path() . "/app/molfiles/{$this->id}.mol";
    }

    public function getpathToSVGAttribute()
    {
        return storage_path() . "/app/svg/{$this->id}.svg";
    }

    public function toMolfile($contents)
    {
        Storage::put("molfiles/{$this->id}.mol", $contents);

        return $this;
    }

    public function toSVG()
    {
        $mol2svg_path = "/usr/local/bin/mol2svg";
        $options = "--bgcolor=white" . " " . "--color=storage/app/colors.conf";
        $command = "{$mol2svg_path} {$options} {$this->pathToMolfile} > {$this->pathToSVG}";

        $pipe = open($command);

        return $this;

    }

}
