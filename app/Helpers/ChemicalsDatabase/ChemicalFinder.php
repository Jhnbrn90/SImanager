<?php

namespace App\Helpers\ChemicalsDatabase;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\RequestException;

class ChemicalFinder 
{
    protected $name;
    protected $cas;

    public function __construct($name) 
    {
        $this->name = $name;
    }

    public function lookupShorthand()
    {
        if ($this->shortHandTable()) {
            return $this->cas;
        }
        return false;   
    }

    public function getCas()
    {
        if($this->shortHandTable()) {
            return $this->cas;
        }

        if($this->searchCommonChemistry()) {
            return $this->cas;
        }

        if($this->searchSigmaAldrich()) {
            return $this->cas;
        }

        if($this->searchWikipedia()) {
            return $this->cas;
        }

        return null;
    }

    protected function shortHandTable()
    {
        $result = DB::table('shorthands')->where('shorthand', $this->name);
        if($result->count() == 0) {
            return false;
        }

        $result = $result->first();

        return $this->cas = $result->cas;
    }

    protected function searchSigmaAldrich()
    {
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://www.sigmaaldrich.com']);

        $body = $client->request('GET',
            '/catalog/search?term='.$this->name.'&interface=All&N=0&mode=match%20partialmax&lang=en&region=NL&focus=product')
            ->getBody();

        // search for ">100-39-0</a>"
        if(preg_match('#([1-9]{1}[0-9]{1,5}-\d{2}-\d)</a>#s', $body, $match)) {
            return $this->cas = $match[1];
        }

        return false;

    }

    protected function searchWikipedia()
    {

        $client = new \GuzzleHttp\Client;

        try {
            $client->request('GET', 'https://en.wikipedia.org/wiki/'.$this->name);
        } catch (RequestException $e) {
            return false;
        }

        $body = $client->request('GET', 'https://en.wikipedia.org/wiki/'.$this->name)
            ->getBody();

        // search for ">100-39-0</a>"
        if(preg_match('#([1-9]{1}[0-9]{1,5}-\d{2}-\d)</a>#s', $body, $match)) {
            return $this->cas = $match[1];
        }

        return false;

    }

    protected function searchCommonChemistry()
    {
        $client = new \GuzzleHttp\Client(['base_uri' => 'http://www.commonchemistry.org']);

        $body = $client->request('GET', 'search.aspx?terms=' . $this->name)
            ->getBody();

        // search for ">100-39-0</a>"
        if(preg_match('#([1-9]{1}[0-9]{1,5}-\d{2}-\d)</a>#s', $body, $match)) {
            return $this->cas = $match[1];
        }

        return false;

    }

    public static function casToName($cas)
    {
        if(! $name = @file_get_contents('https://cactus.nci.nih.gov/chemical/structure/'.$cas.'/iupac_name')) {
            return false;
        }

        return $name;

    }

    public static function casToMolfile($cas)
    {

        if(! $molfile = @file_get_contents('https://cactus.nci.nih.gov/chemical/structure/' . $cas . '/file?format=sdf&operator=remove_hydrogens')) {

            return false;

        }

        return $molfile;

    }
}
