<?php

namespace App\Http\Controllers;

use App\Compound;
use Illuminate\Http\Request;

class CompoundController extends Controller
{
    function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $compounds = auth()->user()->compounds()->orderBy('created_at', 'desc')->get();

        return view('compounds.index', compact('compounds'));
    }

    public function show(Compound $compound)
    {
        // /compounds/1102
        $compound = Compound::findOrFail($compound)->first();

        return view('compounds.show', compact('compound'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|unique:compounds|max:8',
        ]);

        $compound = Compound::create([
            'user_id'               => auth()->id(),
            'label'                 => $request->label,
            'proton_nmr'            => $request->proton,
            'carbon_nmr'            => $request->carbon,
            'retention'             => $request->retention,
            'melting_point'         => $request->melting_point,
            'infrared'              => $request->infrared,
            'mass_adduct'           => $request->mass_adduct,
            'mass_measured'         => $request->mass_mesaured,
            'alpha_sign'            => $request->alpha_sign,
            'alpha_value'           => $request->alpha_value,
            'alpha_concentration'   => $request->alpha_concentration,
            'alpha_solvent'         => $request->alpha_solvent,
            'notes'                 => $request->notes,
        ]);

        // make a new molfile and svg for the molecule
            // takes the $compound->id as filename.
    }

    public function destroy(Compound $compound)
    {
        $compound = Compound::findOrFail($compound)->first();

        $compound->delete();

        return redirect('/');
    }

}
