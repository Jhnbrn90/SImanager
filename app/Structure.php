<?php

namespace App;

use App\Helpers\Facades\Checkmol;
use App\Helpers\Facades\Matchmol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Structure extends Model
{
    protected $guarded = [];

    public function chemical()
    {
        return $this->belongsTo(Chemical::class);
    }

    public function setMolfile($molfile)
    {
        $this->update(['molfile' => $molfile]);
    }

    public function scopeCandidates($query, $properties, $exact = false)
    {
        foreach ($properties as $key => $value) {
            $attributes[] = $exact ? [$key, '=', $value] : [$key, '>=', $value];    
        }
    
        $query->where($attributes);
    }

    public function getSVGPathAttribute()
    {
        if (! $this->molfile) {
            return 'storage/svg/unknown.svg';
        }

        return "storage/database/svg/{$this->id}.svg?".time();
    }

    public function svgPath()
    {
        if (! $this->molfile) {
            return 'storage/svg/unknown.svg';
        }

        return "storage/database/svg/{$this->id}.svg?".time();
    }

    public function getpathToSVGAttribute()
    {
        return storage_path()."/app/public/database/svg/{$this->chemical_id}.svg";
    }

    public function getpathToMolfileAttribute()
    {
        return storage_path()."/app/public/database/molfiles/{$this->chemical_id}.mol";
    }

    public function toMolfile()
    {
        if ($this->molfile[0] == ' ' || $this->molfile[0] == 'J') {
            // insert newline if the first character of the first line is a space or J (from JSDRAW)
            $this->molfile = "\r\n".$this->molfile;
        }

        Storage::put("public/database/molfiles/{$this->id}.mol", $this->molfile);

        return $this;
    }

    public function toSVG()
    {
        if (! $this->molfile) {
            return;
        }

        $output = shell_exec('echo "'.$this->molfile.'" | /usr/local/bin/mol2svg --bgcolor=white --color=colors.conf - > '.$this->pathToSVG);
    }
}
