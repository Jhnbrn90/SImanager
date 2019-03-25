<?php

namespace App\Helpers\ChemicalsDatabase;

use App\Structure;
use App\Helpers\Facades\Checkmol;
use App\Helpers\Facades\Matchmol;

class SubstructureSearch
{
    protected $molfile;

    protected $exact = false;

    public function exact()
    {
        $this->exact = true;

        return $this;
    }

    public function molfile($molfile)
    {
        $this->molfile = $this->prefix($molfile).$molfile;

        return $this;
    }

    public function candidates()
    {
        if (! $properties = Checkmol::properties($this->molfile)) {
            $properties = Checkmol::properties("\n".$this->molfile);
        }

        return Structure::candidates($properties, $exact = $this->exact)->get();
    }

    public function matches()
    {
        $structureIds = collect($this->candidates())->map(function ($structure) {
            return $structure->id;
        });

        $matchingStructureIds = Matchmol::match($this->molfile, $structureIds)->substructure($this->exact);

        $matches = Structure::with('chemical')
            ->whereIn('id', $matchingStructureIds)
            ->orderBy('n_atoms', 'ASC')
            ->get();

        return $matches;
    }

    protected function prefix($molfile)
    {
        return ($molfile[0][0] == ' ' || $molfile[0][0] == 'J') ? "\n" : '';
    }
}
