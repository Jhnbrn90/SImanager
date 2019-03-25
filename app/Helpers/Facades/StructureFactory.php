<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class StructureFactory extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'structure-factory';
    }
}
