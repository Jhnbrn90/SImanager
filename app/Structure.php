<?php

namespace App;

use App\Helpers\Facades\Checkmol;
use App\Helpers\Facades\Matchmol;
use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    protected $guarded = [];

    protected $with = ['structurable'];

    public function structurable()
    {
        return $this->morphTo();
    }

    public function setMolfile($molfile)
    {
        $this->update(['molfile' => $molfile]);
    }

    public function scopeCandidates($query, $molfile, $exact = false)
    {
        $molfile = $this->formatMolfile($molfile);
        $properties = Checkmol::properties($molfile);

        foreach ($properties as $key => $value) {
            $attributes[] = $exact ? [$key, '=', $value] : [$key, '>=', $value];
        }

        $query->where($attributes);
    }

    public function scopeMatches($query, $molfile, $exact = false)
    {
        $molfile = $this->formatMolfile($molfile);
        $candidates = self::candidates($molfile, $exact)->get();

        $structureIds = collect($candidates)->map(function ($structure) {
            return $structure->id;
        });

        $matchingStructureIds = Matchmol::match($molfile, $structureIds)->substructure($exact);

        return $query->whereIn('id', $matchingStructureIds)->orderBy('n_atoms', 'ASC');
    }

    public function scopeChemicals($query)
    {
        return $query->where('structurable_type', 'App\Chemical');
    }

    public function saveSvg()
    {
        if (! $this->molfile) {
            return;
        }

        $output = shell_exec(
            'echo "'.$this->molfile.'" | /usr/local/bin/mol2svg --bgcolor=white --color=colors.conf -'
        );

        $this->update(['svg' => $output]);
    }

    protected function formatMolfile($molfile)
    {
        $prefix = ($molfile[0][0] == ' ' || $molfile[0][0] == 'J') ? "\n" : '';

        return $prefix.$molfile;
    }
}
