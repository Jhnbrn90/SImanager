<?php

namespace App;

use App\Helpers\Checkmol;
use App\Helpers\Matchmol;
use App\Helpers\BashCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Structure extends Model
{
    protected $guarded = [];

    public function chemical()
    {
        return $this->belongsTo(Chemical::class);
    }

    public static function makeFromJSDraw($query)
    {
        $query = "\n"."$query";

        return self::make(
            Checkmol::propertiesFor($query)
        );
    }

    public static function createFromJSDraw($query)
    {
        $query = "\n"."$query";

        return self::create(
            Checkmol::propertiesFor($query)
        );
    }

    public static function makeFromMolfile($molfile)
    {
        return self::make(
            Checkmol::propertiesFor($molfile)
        );
    }

    public static function createFromMolfile($molfile)
    {
        return self::create(
            Checkmol::propertiesFor($molfile)
        );
    }

    public function getCandidates($exact = null)
    {
        foreach ($this->toArray() as $key => $value) {
            if ($key !== 'molfile') {
                // Build up the query string
                // Example: n_atoms, '>=', 3
                $query[] = $exact ? [$key, '=', $value] : [$key, '>=', $value];
            }
        }

        return self::where($query)->get();
    }

    public function getCandidatesAttribute()
    {
        return $this->getCandidates();
    }

    public function getMatchesAttribute()
    {
        $queryStructure = $this->molfile;

        $candidateIds = $this->getCandidates()->map(function ($candidate) {
            return $candidate->id;
        });

        $matchingStructureIds = Matchmol::match($queryStructure, $candidateIds)->substructure();

        $matchingStructures = self::with('chemical')
            ->whereIn('id', $matchingStructureIds)
            ->orderBy('n_atoms', 'ASC')
            ->get();

        return $matchingStructures;
    }

    public function getExactMatchesAttribute()
    {
        $queryStructure = $this->molfile;

        $candidateIds = $this->getCandidates($exact = true)->map(function ($candidate) {
            return $candidate->id;
        });

        $matchingStructureIds = Matchmol::match($queryStructure, $candidateIds)->exact();

        return self::find($matchingStructureIds);
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
