<?php

namespace App;

use App\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Compound extends Model
{

    protected $guarded = [];

    protected $casts = [
        'user_id'   => 'int',
        'id'        => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
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

    public function svgPath()
    {
        if (! $this->molfile) {
         return "storage/svg/unknown.svg";
        }

        return "storage/svg/{$this->id}.svg?".time();   
    }

    public function getSVGPathAttribute()
    {
        if (! $this->molfile) {
            return "storage/svg/unknown.svg";
        }
        
        return "storage/svg/{$this->id}.svg?".time();
    }

    public function toMolfile()
    {
        if($this->molfile[0] == " " || $this->molfile[0] == "J") {
            // insert newline if the first character of the first line is a space or J (from JSDRAW)
            $this->molfile = "\r\n" . $this->molfile;
        }

        Storage::put("public/molfiles/{$this->id}.mol", $this->molfile);

        return $this;
    }

    public function toSVG()
    {
        if (! $this->molfile) {
            return;
        }

        if (! file_exists(storage_path() . "/app/public/molfiles/{$this->id}.mol")) {
            $this->toMolfile();
            
            if (! file_exists(storage_path() . "/app/public/molfiles/{$this->id}.mol")) {
                throw new \Exception('Error creating the molfile for this structure.');
            }
        }

        $mol2svg_path = "/usr/local/bin/mol2svg";
        $options = "--bgcolor=white" . " " . "--color=colors.conf";

        $command = "{$mol2svg_path} {$options} {$this->pathToMolfile} > {$this->pathToSVG}";

        $pipe = popen($command, "r");

        return $this;
    }

    public function formattedAlphaSolvent()
    {
        return $this->formatFormula($this->alpha_solvent);
    }

    public function getFormattedFormulaAttribute()
    {
        return $this->formatFormula($this->formula);
    }

    public function formattedFormulaForHRMS()
    {
        switch ($this->mass_adduct) {
            case 'Na+':
                $formula = $this->formatFormula($this->formula);
                $formula .= 'Na [M+Na]<sup>+</sup>';
            break;

            case 'H+':
                $formulaPlusProton = $this->modifyFormula($this->formula, 'add', 1, 'H');
                $formula = $this->formatFormula($formulaPlusProton);
                $formula .= ' [M+H]<sup>+</sup>';
            break;

            case 'H-':
                $formulaMinusProton = $this->modifyFormula($this->formula, 'subtract', 1, 'H');
                $formula = $this->formatFormula($formulaMinusProton);
                $formula .= ' [M-H]<sup>+</sup>';
            break;
            default:
                $formula = $this->formula;
            break;
        }

        return $formula;
    }

    public function formattedProtonNMR()
    {
        $data = $this->H_NMR_data;

        $data = preg_replace('/Chloroform-d/', 'CDCl3', $data);    
    
        $data = preg_replace('/1H\s+NMR/', '<strong><sup>1</sup>H NMR</strong>', $data);
        $data = preg_replace('/([A-Z][a-z]?)(\d+)/', '${1}<sub>${2}</sub>', $data);
        $data = preg_replace('/[J]\s=/', '<em>J</em> =', $data);

        if(substr($data, -1) !== ".") {
            $data .= ".";
        }

        return $data;
    }

    public function formattedCarbonNMR()
    {
        $data = $this->C_NMR_data;
        $data = preg_replace('/13C\s+NMR/', '<strong><sup>13</sup>C NMR</strong>', $data);
        $data = preg_replace('/([A-Z][a-z]?)(\d+)/', '${1}<sub>${2}</sub>', $data);

        if(substr($data, -1) !== ".") {
            $data .= ".";
        }

        return $data;
    }

    public function getformulaProtonsAttribute()
    {
        if (empty($this->formula)) {
            return;
        }

        $regex = '/H(\d+)/';

        preg_match($regex, $this->formula, $matches);

        return $matches[1];
    }

    public function getformulaCarbonsAttribute()
    {
        if (empty($this->formula)) {
            return;
        }
        
        $regex = '/C(\d+)/';

        preg_match($regex, $this->formula, $matches);

        return $matches[1];
    }

    public function getnmrProtonsAttribute()
    {
        $regex = '/,\s*(\d+)\s*H/';

        preg_match_all($regex, $this->H_NMR_data, $matches);

        return collect($matches[1])->sum();
    }

    public function getnmrCarbonsAttribute()
    {
        $regex = '/(\d+\.\d+)\s*,/';

        preg_match_all($regex, $this->C_NMR_data, $matches);

        return collect($matches[1])->count();
    }

    public function checkProtonNMR()
    {
        return $this->formulaProtons == $this->nmrProtons;
    }

    public function checkCarbonNMR()
    {
        return $this->formulaCarbons == $this->nmrCarbons;
    }

    protected function formatFormula($formula)
    {
        $formattedFormula = '';
        preg_match_all('/([A-Z][a-z]?)(\d*)/', $formula, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $formattedFormula .= $match[1];

            if ($match[2] !== "" && $match[2] !== "1") {
                $formattedFormula .= '<sub>' . $match[2] . '</sub>';
            }
        }

        return $formattedFormula;
    }

    protected function modifyFormula($formula, $operation, $count, $atom)
    {
        preg_match_all('/([A-Z][a-z]?)(\d*)/', $formula, $matches, PREG_SET_ORDER);
        
        $modifiedFormula = '';

        foreach ($matches as $match) {
            if ($match[1] == $atom) {
                if ($operation == 'add') {
                    $modifiedFormula .= $match[1] . ($match[2] + $count);
                } 
                if ($operation == 'subtract') {
                    $modifiedFormula .= $match[1] . ($match[2] - $count);  
                }
            } else {
                $modifiedFormula .= $match[1] . $match[2];                       
            }
        }

        return $modifiedFormula;
    }
}
