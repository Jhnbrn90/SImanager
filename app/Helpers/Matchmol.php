<?php

namespace App\Helpers;

use App\Structure;

class Matchmol {

    protected $binary = "/usr/local/bin/matchmol";

    protected $queryStructure;
    protected $candidateIds;
    protected $candidateCount;
    protected $candidatesPerQuery = 10;
    protected $candidatesArray;

    public function __construct($queryStructure, $candidateIds)
    {
        $this->queryStructure   = $queryStructure;
        $this->candidateIds     = $candidateIds;
        $this->candidateCount   = $candidateIds->count();

        $this->buildCandidatesArray();
    }

    public static function match($queryStructure, $candidateIds)
    {
        return new Matchmol($queryStructure, $candidateIds);   
    }

    public function substructure()
    {
        // Number of times Matchmol has to be called with the maximum candidatesPerQuery
        $blocks = ceil($this->candidateCount / $this->candidatesPerQuery);

        $matches = [];

        for($i = 1; $i <= $blocks; $i++) {
            $matchResults = BashCommand::run($this->buildQuery($i), $this->binary, '-');
            $resultsArray = $this->generateResultsArray($matchResults);

            $matches[] = $this->filterMatchingCanidateIds($resultsArray, $i);
        }

        $result = array_flatten($matches);

        return $result;
    }

    public function exact()
    {
        $matchResults = BashCommand::run($this->buildQuery(1), $this->binary, '-x -');

        $resultsArray = $this->generateResultsArray($matchResults);

        return $this->filterMatchingCanidateIds($resultsArray, 1);
    }

    protected function buildQuery($cycle)
    {
        $offset = ($cycle-1) * $this->candidatesPerQuery;

        // slice the candidates and prepend the target structure
        $candidates = array_slice($this->candidatesArray, $offset, $this->candidatesPerQuery);

        array_unshift($candidates, $this->trimFormula($this->queryStructure));

        return $this->formatQuery($candidates);
    }

    protected function trimFormula($molfile)
    {
        return preg_replace("/^[A-Z]+\d+.*/", "", $molfile);
    }

    protected function formatQuery($molfilesArray) {

        // remove all present '$$$$' separators
        foreach ($molfilesArray as $molfile) {
            $strippedMolfileArray[] = preg_replace('/\$+/', '', $molfile);
        }

        $molfileString = implode($strippedMolfileArray, "\n $$$$ \n");
        $molfileString = preg_replace('/\$+$/', '', $molfileString);

        return str_replace('$', '\$', $molfileString);
    }

    protected function generateResultsArray($matchResults)
    {
        $resultsArray = explode("\n", $matchResults);
        $result = array_splice($resultsArray, 0, -1);

        return $result;
    }

    protected function filterMatchingCanidateIds($resultsArray, $cycle)
    {
        $resultIds = [];

        // array filter
        foreach($resultsArray as $result) {
            $match = explode(":", trim($result));
            if($match[1] == "T") {
                $resultIds[] = $match[0];
            }
        }

        $offset = ($cycle-1) * $this->candidatesPerQuery;

        $matchingCandidateIds = [];

        foreach($resultIds as $resultId) {
            $id = $resultId-1;
            $matchingCandidateIds[] = $this->candidateIds[$id+$offset];
        }

        return $matchingCandidateIds;
    }

    private function buildCandidatesArray()
    {
        $molfilesArray = [];

        foreach ($this->candidateIds as $candidate) {
            $molfilesArray[] = $this->trimFormula(
                Structure::find($candidate)->molfile
            );
        }

        $this->candidatesArray = $molfilesArray;
    }
}
