<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class SubstructureSearch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'substructure-search';
    }
}
