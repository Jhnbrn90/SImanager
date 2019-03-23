<?php

namespace App\Helpers;

class Checkmol
{
    protected $binary = '/usr/local/bin/checkmol';
    protected $molfile;

    public function __construct($molfile)
    {
        $this->molfile = $molfile;
    }

    public static function propertiesFor($molfile)
    {
        return (new self($molfile))->properties();
    }

    public function properties()
    {
        $propertiesString = BashCommand::run($this->molfile, $this->binary, '-x -');

        if (substr($propertiesString, 0, -1) === 'invalid molecule') {
            return false;
        }

        $propertiesString = substr($propertiesString, 0, -2);

        $propertiesArray = collect(explode(';', $propertiesString))
           ->flatMap(function ($attribute) {
               $keyValuePairs = explode(':', $attribute);

               return [$keyValuePairs[0] => $keyValuePairs[1]];
           })->toArray();

        $propertiesArray['molfile'] = $this->molfile;

        return $propertiesArray;
    }
}
