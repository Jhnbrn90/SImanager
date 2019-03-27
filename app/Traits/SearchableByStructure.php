<?php

namespace App\Traits;

trait SearchableByStructure
{
    public function structure()
    {
        return $this->morphOne('App\Structure', 'structurable');
    }
}
