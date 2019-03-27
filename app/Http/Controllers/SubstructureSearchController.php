<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Facades\SubstructureSearch;

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
        session(['substructure_search' => $request->molfile]);

        $substructureSearch = SubstructureSearch::molfile($request->molfile);

        $matches = $request->exact ? $substructureSearch->exact()->matches() : $substructureSearch->matches();

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
