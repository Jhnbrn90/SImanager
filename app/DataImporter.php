<?php

namespace App;

class DataImporter
{
    protected $experiment;

    protected $regexLookup = [
        'protonNMR'     =>  '/((?:1H|1-H)\s*NMR.*?)\.\s/',
        'carbonNMR'     => '/((?:13C|13-C)\s*NMR.*?)\.\s/',
        'rfValue'       => '/(?:Rf|RF|rf|rF)\s*(?:=|\:)\s*(\d\.\d+\s*\(.*?\))/',
        'irData'        => '/(?:IR|Ir).*?(?:\:|=)\s*(\d.*?)\.\s/',
        'meltingPoint'  => '/\.\s*(?:melting point|M\.?p\.?|M\.?P\.?|m\.?p\.?)\s*(?::|=)?\s*(\d*).*?(?:c|C)\./i',
        'HRMS'          => '/for\s*((?:[A-Z]+[a-z]?\d*)+).*?(\d*\.\d*).*?found.*?(\d\d*\.?\d*)/',
        'rotation'      => '/=\s*([^\d\sA-Za-z]{1})\s*(\d*\.?\d*)\s*\(.*?=\s*(\d*\.?\d*),?\s*(.*?)\)/',
    ];

    public function __construct(string $experiment)
    {
        $this->experiment = $experiment;
    }

    public function getProtonNMR()
    {
        return $this->match('protonNMR');
    }

    public function getCarbonNMR()
    {
        return $this->match('carbonNMR');
    }

    public function getRfValue()
    {
        return $this->match('rfValue');
    }

    public function getIrData()
    {
        return $this->match('irData');   
    }

    public function getMeltingPoint()
    {
        return $this->match('meltingPoint');   
    }

    public function getHRMS($type)
    {
        switch ($type) {
            case 'formula':
                return $this->matchMultiple('HRMS')[1];
            case 'calculated':
                return $this->matchMultiple('HRMS')[2];
            case 'found':
                return $this->matchMultiple('HRMS')[3];
            default:
                return;
        }
    }

    public function getRotation($type)
    {
        switch ($type) {
            case 'sign':
                return $this->matchMultiple('rotation')[1];
            case 'value':
                return $this->matchMultiple('rotation')[2];
            case 'concentration':
                return $this->matchMultiple('rotation')[3];
            case 'solvent':
                return $this->matchMultiple('rotation')[4];
            default:
                return;
        }
    }

    protected function match($lookup)
    {
        $regex = $this->regexLookup[$lookup];
        
        preg_match($regex, $this->experiment, $match);
        
        return $match[1];
    }

    protected function matchMultiple($lookup)
    {
        $regex = $this->regexLookup[$lookup];
        
        preg_match($regex, $this->experiment, $matches);
        
        return $matches;
    }
}
