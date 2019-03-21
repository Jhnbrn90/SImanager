<?php

namespace App\Http\Controllers;

use App\Structure;
use Illuminate\Http\Request;

class SubstructureSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function index()
    {
        $molfile = session('substructure_search');
        return view('database.substructure.index', compact('molfile'));
    }

    public function show(Request $request)
    {
        $queryStructure = Structure::makeFromJSDraw($request->molfile);
        
        session(['substructure_search' => $request->molfile]);

        $matches = $queryStructure->matches;

        $matches = $matches->map(function ($structure) { return $structure->chemical; });

        return view('database.substructure.show', compact('matches'));
    }

    public function reset()
    {
        session()->forget('substructure_search');
        
        return redirect('/database/substructure');
    }
}
