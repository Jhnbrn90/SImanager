<?php

namespace App\Helpers;

use App\Structure;
use App\Helpers\Facades\Checkmol;

class StructureFactory
{
    protected $molfile;

    public function molfile($molfile)
    {
        $this->molfile = $molfile;

        return $this;
    }

    public function jsdraw($molfile)
    {
        $this->molfile = "\n" . $molfile;

        return $this;
    }

    public function properties()
    {
        return Checkmol::properties($this->molfile);
    }

    public function make()
    {
        $properties = Checkmol::properties($this->molfile);

        return factory(Structure::class)->make($properties);
    }

    public function create($attributes = [])
    {
        $attributes = array_merge($this->properties(), $attributes);
        $structure = tap(Structure::create($attributes))->setMolfile($this->molfile);

        return $structure;
    }
}
