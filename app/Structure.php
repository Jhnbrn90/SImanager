<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Structure extends Model
{
    protected $guarded = [];

    public function chemical()
    {
        return $this->belongsTo(Chemical::class);
    }

    public function getSVGPathAttribute()
    {
        if (! $this->molfile) {
            return "storage/svg/unknown.svg";
        }
        
        return "storage/database/svg/{$this->id}.svg?".time();
    }

    public function svgPath()
    {
        if (! $this->molfile) {
         return "storage/svg/unknown.svg";
        }

        return "storage/database/svg/{$this->id}.svg?".time();   
    }

    public function getpathToSVGAttribute()
    {
        return storage_path() . "/app/public/database/svg/{$this->id}.svg";
    }

    public function getpathToMolfileAttribute()
    {
        return storage_path() . "/app/public/database/molfiles/{$this->id}.mol";
    }

    public function toMolfile()
    {
        if($this->molfile[0] == " " || $this->molfile[0] == "J") {
            // insert newline if the first character of the first line is a space or J (from JSDRAW)
            $this->molfile = "\r\n" . $this->molfile;
        }

        Storage::put("public/database/molfiles/{$this->id}.mol", $this->molfile);

        return $this;
    }

    public function toSVG()
    {
        if (! $this->molfile) {
            return;
        }

        if (! file_exists(storage_path() . "/app/public/database/molfiles/{$this->id}.mol")) {
            $this->toMolfile();
            
            if (! file_exists(storage_path() . "/app/public/database/molfiles/{$this->id}.mol")) {
                throw new \Exception('Error creating the molfile for structure: ' .$this->id);
            }
        }

        $mol2svg_path = "/usr/local/bin/mol2svg";
        $options = "--bgcolor=white" . " " . "--color=colors.conf";

        $command = "{$mol2svg_path} {$options} {$this->pathToMolfile} > {$this->pathToSVG}";

        $pipe = popen($command, "r");

        return $this;
    }

}
