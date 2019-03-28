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
        $request->validate(['molfile' => 'required']);
        $exact = $request->exact ? true : false;
        session(['substructure_search' => $request->molfile]);

        $matches = Structure::chemicals()->matches($request->molfile, $exact)->get();

        $matches = $matches->map(function ($structure) {
            return $structure->structurable;
        });

        return view('database.substructure.show', compact('matches'));
    }

    public function reset()
    {
        session()->forget('substructure_search');

        return redirect('/database/substructure');
    }
}
