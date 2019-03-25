<?php

namespace Tests\Setup;

use App\Structure;
use App\Helpers\Facades\StructureFactory;

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

        $structure = StructureFactory::molfile($this->molfile)->create(['chemical_id' => $chemical->id]);

        $chemical->update(['structure_id' => $structure->id]);

        return $chemical;
    }
}
