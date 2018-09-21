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

    public function getSVGPathAttribute()
    {
        if (!$this->molfile) {
            return "svg/unknown.svg";
        }
        
        return "svg/{$this->id}.svg";
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

    public function formattedAlphaSolvent()
    {
        $formula = '';

        preg_match_all('/([A-Z][a-z]?)(\d*)/', $this->alpha_solvent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $formula .= $match[1] . '<sub>' . $match[2] . '</sub>';                       
        }

        return $formula;
    }

    public function getFormattedFormulaAttribute()
    {
        $formula = '';

        preg_match_all('/([A-Z][a-z]?)(\d*)/', $this->formula, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $formula .= $match[1] . '<sub>' . $match[2] . '</sub>';                       
        }

        return $formula;
    }

    public function formattedFormulaForHRMS()
    {
        // C12H5SO3
        $formula = '';

        switch ($this->mass_adduct) {
            case 'Na+':
                preg_match_all('/([A-Z][a-z]?)(\d*)/', $this->formula, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $formula .= $match[1] . '<sub>' . $match[2] . '</sub>';                       
                }

                $formula .= 'Na';

                $formula .= ' [M+Na]<sup>+</sup>';
            break;

            case 'H+':
                preg_match_all('/([A-Z][a-z]?)(\d*)/', $this->formula, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    if ($match[1] == "H") {
                        $formula .= $match[1] . '<sub>' . ($match[2] + 1) . '</sub>';                       
                    } else {
                        $formula .= $match[1] . '<sub>' . $match[2] . '</sub>';                       
                    }
                }

                $formula .= ' [M+H]<sup>+</sup>';
            break;

            case 'H-':
                preg_match_all('/([A-Z][a-z]?)(\d*)/', $this->formula, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    if ($match[1] == "H") {
                        $formula .= $match[1] . '<sub>' . ($match[2] - 1) . '</sub>';                       
                    } else {
                        $formula .= $match[1] . '<sub>' . $match[2] . '</sub>';                       
                    }
                }

                $formula .= '[M-H]<sup>+</sup>';
            break;
            default:
                $formula = $this->formula;
            break;
        }

        return $formula;
    }

}
