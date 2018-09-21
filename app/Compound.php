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
        return storage_path() . "/app/public/molfiles/{$this->id}.mol";
    }

    public function getpathToSVGAttribute()
    {
        return storage_path() . "/app/public/svg/{$this->id}.svg";
    }

    public function toMolfile()
    {
        if($this->molfile[0] == " " || $this->molfile[0] == "J") {
            // if the first character of the first line is a space or J (from JSDRAW)
            // then insert a newline
            
            Storage::put("public/molfiles/{$this->id}.mol", "\r\n" . $this->molfile);
            
            return $this;
        }

        Storage::put("public/molfiles/{$this->id}.mol", $this->molfile);

        return $this;
    }

    public function toSVG()
    {
        $mol2svg_path = "/usr/local/bin/mol2svg";
        $options = "--bgcolor=white" . " " . "--color=colors.conf";

        $command = "{$mol2svg_path} {$options} {$this->pathToMolfile} > {$this->pathToSVG}";

        $pipe = popen($command, "r");

        return $this;
    }

}
