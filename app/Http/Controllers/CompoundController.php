<?php

namespace App\Http\Controllers;

use App\User;
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

    public function edit(Compound $compound)
    {
        return view('compounds.edit', compact('compound'));
    }

    public function studentIndex(User $user)
    {
        // check if the provided user has the logged in user as a supervisor
        if (!$this->isSupervisorOf($user)) {
            return redirect('/');
        }

        $compounds = $user->compounds()->orderBy('created_at', 'desc')->get();

        return view('compounds.index', compact('compounds'));
    }

    public function show(Compound $compound)
    {
        if(auth()->id() !== $compound->user_id && !$this->isSupervisorOf($compound->user_id)) {
            return redirect('/');
        }

        return view('compounds.show', compact('compound'));    
    }

    public function create()
    {
        return view('compounds.create');
    }

    public function store(Request $request)
    {
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
            'molfile'               => $request->molfile,
            'molweight'             => $request->molweight,
            'formula'               => $request->formula,
            'exact_mass'            => $request->exact_mass,
        ]);

        $compound->toMolfile()->toSVG();

        return redirect('/');
    }

    public function update(Compound $compound, Request $request)
    {
        if(auth()->id() !== $compound->user_id && !$this->isSupervisorOf($compound->user_id)) {
            return redirect('/');
        }
        
        $compound->update([$request->column => $request->value]);
        
        return response()->json($compound, 201);
    }

    public function updateAll(Compound $compound, Request $request)
    {
        if(auth()->id() !== $compound->user_id && !$this->isSupervisorOf($compound->user_id)) {
            return redirect('/');
        }

        $proton_NMR = false;
        $carbon_NMR = false;

        if($request->NMR) {
            $proton_NMR = in_array('H_NMR', $request->NMR);
            $carbon_NMR = in_array('C_NMR', $request->NMR);
        }

        $compound->label = $request->label;
        $compound->proton_nmr = $proton_NMR;
        $compound->carbon_nmr = $carbon_NMR;
        $compound->retention = $request->Rf;
        $compound->melting_point = $request->MP;
        $compound->infrared = $request->IR;
        $compound->mass_measured = $request->mass_found;
        $compound->mass_calculated = $request->mass_calculated;
        $compound->mass_adduct = $request->mass_ion;
        $compound->alpha_sign = $request->rotation_sign;
        $compound->alpha_value = $request->rotation_value;
        $compound->alpha_concentration = $request->rotation_concentration;
        $compound->alpha_solvent = $request->rotation_solvent;
        $compound->notes = $request->notes;

        if ($request->user_updated_molfile == 'true') {
            $compound->molfile = $request->molfile;
            $compound->molweight = $request->molweight;
            $compound->formula = $request->formula;
            $compound->exact_mass = $request->exact_mass;
        }
        

        $compound->save();

        if ($request->user_updated_molfile == 'true') {
            $compound->toMolfile()->toSVG();
        }

        return redirect('/compounds/'.$compound->id);

    }

    public function destroy(Compound $compound)
    {
        $compound = Compound::findOrFail($compound)->first();

        $compound->delete();

        return redirect('/');
    }

    public function isSupervisorOf($user)
    {
        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        return $user->supervisors->contains(auth()->user());
    }

}
