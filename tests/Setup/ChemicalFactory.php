<?php

namespace Tests\Setup;

use App\Structure;

class ChemicalFactory
{
    protected $name;

    protected $molfile;

    public function named($name = null)
    {
        $this->name = $name;

        return $this;
    }

    public function withStructure($molfile)
    {
        $this->molfile = $molfile;

        return $this;
    }

    public function create()
    {
        $chemical = $this->name ? create('App\Chemical', ['name' => $this->name]) : create('App\Chemical');
        
        $structure = Structure::createFromMolfile($this->molfile);

        $chemical->structure_id = $structure->id;
        $structure->chemical_id = $chemical->id;

        return $chemical;
    }
}
