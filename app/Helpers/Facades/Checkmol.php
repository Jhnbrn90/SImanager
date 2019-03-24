<?php

namespace App\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class Checkmol extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'checkmol';
    }
}
