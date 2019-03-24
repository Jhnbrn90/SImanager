<?php

namespace App\Helpers;

class Checkmol
{
    protected $binary = '/usr/local/bin/checkmol';

    protected $molfile;

    public function properties($molfile)
    {
        $propertiesString = BashCommand::run($molfile, $this->binary, '-x -');

        if (substr($propertiesString, 0, -1) === 'invalid molecule') {
            return false;
        }

        $propertiesString = substr($propertiesString, 0, -2);

        $propertiesArray = collect(explode(';', $propertiesString))
           ->flatMap(function ($attribute) {
               $keyValuePairs = explode(':', $attribute);

               return [$keyValuePairs[0] => $keyValuePairs[1]];
           })->toArray();

        $propertiesArray['molfile'] = $molfile;

        return $propertiesArray;
    }
}
