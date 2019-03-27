<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Structure extends Model
{
    protected $guarded = [];

    public function structurable()
    {
        return $this->morphTo();
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
}
