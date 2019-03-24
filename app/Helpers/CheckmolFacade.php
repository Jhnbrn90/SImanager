<?php 

namespace App\Helpers;

use Illuminate\Support\Facades\Facade;

class CheckMolFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'checkmol';
    }
}
