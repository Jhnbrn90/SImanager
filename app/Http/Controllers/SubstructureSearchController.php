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

        $structures = Structure::chemicals()
            ->matches($request->molfile, $exact)
            ->with('structurable')
            ->get();

        return view('database.substructure.show', compact('structures'));
    }

    public function reset()
    {
        session()->forget('substructure_search');

        return redirect('/database/substructure');
    }
}
