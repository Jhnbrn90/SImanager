<?php

namespace App\Http\Controllers;

use App\User;
use App\Project;
use App\Compound;
use App\DataImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompoundController extends Controller
{
    function __construct()
    {
        $this->middleware('auth');
    }

    public function index($orderByColumn = 'created_at', $orderByMethod = 'desc', Request $request)
    {
        if($request->order && $request->by) {
            $orderByColumn = $request->by;
            $orderByMethod = $request->order;
        }

        $user = auth()->user();

        $bundles = $user->bundles()
                ->with(['projects.compounds' => function($query) use ($orderByColumn, $orderByMethod) {
                        return $query->orderBy($orderByColumn, $orderByMethod);
                    }])->get();

        return view('compounds.index', compact('user', 'bundles', 'orderByColumn', 'orderByMethod'));
    }

    public function edit(Compound $compound)
    {
        return view('compounds.edit', compact('compound'));
    }

    public function studentIndex(User $user, $orderByColumn = 'created_at', $orderByMethod = 'desc', Request $request)
    {
        if (Gate::denies('access-compounds', $user)) {
            return redirect('/');
        }

        if($request->order && $request->by) {
            $orderByColumn = $request->by;
            $orderByMethod = $request->order;
        }

        $bundles = $user->bundles()
                ->with(['projects.compounds' => function($query) use ($orderByColumn, $orderByMethod) {
                        return $query->orderBy($orderByColumn, $orderByMethod);
                    }])->get();

        return view('compounds.index', compact('bundles', 'user', 'orderByColumn', 'orderByMethod'));
    }

    public function show(Compound $compound)
    {
        if (Gate::denies('interact-with-compound', $compound)) {
            return redirect('/');
        }

        return view('compounds.show', compact('compound'));    
    }

    public function create()
    {
        return view('compounds.create');
    }

    public function import()
    {
        return view('compounds.import');
    }

    public function store(Request $request)
    {
        $project = Project::findOrFail($request->project);

        $compound = Compound::create([
            'user_id'               => $project->user->id,
            'project_id'            => $project->id,
            'label'                 => $request->label ?? '(unkown)',
            'H_NMR_data'            => $request->H_NMR,
            'C_NMR_data'            => $request->C_NMR,
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

        if ($project->user->id !== auth()->id()) {
            return redirect('/students/view/data/' . $project->user->id);
        }

        return redirect('/');
    }

    public function storeFromImport(Request $request)
    {
        $request->validate([
            'project' => 'required',
            'experimental'  => 'required',
        ]);

        $importer = new DataImporter($request->experimental);

        $project = Project::findOrFail($request->project);

        $compound = Compound::create([
            'user_id'               => $project->user->id,
            'project_id'            => $project->id,
            'label'                 => $request->label ?? '(unkown)',
            'H_NMR_data'            => $importer->getProtonNMR(),
            'C_NMR_data'            => $importer->getCarbonNMR(),
            'retention'             => $importer->getRfValue(),
            'melting_point'         => $importer->getMeltingPoint(),
            'infrared'              => $importer->getIrData(),
            'mass_adduct'           => $importer->getHRMS('ion'),
            'mass_measured'         => $importer->getHRMS('found'),
            'mass_calculated'       => $importer->getHRMS('calculated'),
            'alpha_sign'            => $importer->getRotation('sign'),
            'alpha_value'           => $importer->getRotation('value'),
            'alpha_concentration'   => $importer->getRotation('concentration'),
            'alpha_solvent'         => $importer->getRotation('solvent'),
            'notes'                 => $request->notes,
            'molfile'               => $request->molfile,
            'molweight'             => $request->molweight,
            'formula'               => $request->formula,
            'exact_mass'            => $request->exact_mass,
        ]);

        $compound->toMolfile()->toSVG();

        if ($project->user->id !== auth()->id()) {
            return redirect('/students/view/data/' . $project->user->id);
        }

        return redirect('/');
    }

    public function update(Compound $compound, Request $request)
    {
        if (Gate::denies('interact-with-compound', $compound)) {
            return redirect('/');
        }
        
        $compound->update([$request->column => $request->value]);
        
        return response()->json($compound, 201);
    }

    public function updateAll(Compound $compound, Request $request)
    {
        if (Gate::denies('interact-with-compound', $compound)) {
            return redirect('/');
        }

        $compound->label = $request->label;
        $compound->project_id = $request->project;
        $compound->H_NMR_data = $request->H_NMR;
        $compound->C_NMR_data = $request->C_NMR;
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
        if (Gate::denies('interact-with-compound', $compound)) {
            return redirect('/');
        }

        $compound = Compound::findOrFail($compound)->first();

        $compound->delete();

        return redirect('/');
    }

    public function confirmDelete(Compound $compound)
    {
        return view('compounds.confirmdelete', compact('compound'));
    }

}
