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
        return view('compounds.create');
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'label' => 'required|unique:compounds|max:8',
        // ]);

        $proton_NMR = false;
        $carbon_NMR = false;

        if($request->NMR) {
            $proton_NMR = in_array('H_NMR', $request->NMR);
            $carbon_NMR = in_array('C_NMR', $request->NMR);
        }

        $compound = Compound::create([
            'user_id'               => auth()->id(),
            'label'                 => $request->label,
            'proton_nmr'            => $proton_NMR,
            'carbon_nmr'            => $carbon_NMR,
            'retention'             => $request->Rf,
            'melting_point'         => $request->MP,
            'infrared'              => $request->IR,
            'mass_adduct'           => $request->mass_ion,
            'mass_measured'         => $request->mass_found,
            'mass_calculated'       => $request->mass_calculated,
            'alpha_sign'            => $request->rotation_sign,
            'alpha_value'           => $request->rotation_value,
            'alpha_concentration'   => $request->rotation_concentration,
            'alpha_solvent'         => $request->rotation_solvent,
            'notes'                 => $request->notes,
            'molfile'               => $request->molfile
        ]);

        $compound->toMolfile()->toSVG();

        return redirect('/');
    }

    public function destroy(Compound $compound)
    {
        $compound = Compound::findOrFail($compound)->first();

        $compound->delete();

        return redirect('/');
    }

}
