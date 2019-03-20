<?php

namespace App\Http\Controllers;

use App\User;
use App\Project;
use App\Compound;
use App\DataImporter;
use Illuminate\Http\Request;

class CompoundController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        
        $orderByColumn = $this->orderByColumn($request);
        $orderByMethod = $this->orderByMethod($request);

        $bundles = $user->bundles()
                ->with(['projects.compounds' => function($query) use ($orderByColumn, $orderByMethod) {
                        return $query->orderBy($orderByColumn, $orderByMethod);
                    }])->get();

        return view('compounds.index', compact('user', 'bundles', 'orderByColumn', 'orderByMethod'));
    }

    public function edit(Compound $compound)
    {
        $this->authorize('interact-with-compound', $compound);
        
        return view('compounds.edit', compact('compound'));
    }

    public function studentIndex(User $user, Request $request)
    {
        $this->authorize('interact-with-compound', $compound);

        $orderByColumn = $this->orderByColumn($request);
        $orderByMethod = $this->orderByMethod($request);

        $bundles = $user->bundles()
                ->with(['projects.compounds' => function($query) use ($orderByColumn, $orderByMethod) {
                        return $query->orderBy($orderByColumn, $orderByMethod);
                    }])->get();

        return view('compounds.index', compact('bundles', 'user', 'orderByColumn', 'orderByMethod'));
    }

    public function show(Compound $compound)
    {
        $this->authorize('interact-with-compound', $compound);

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
        $project = Project::findOrFail($request->project_id);

        $this->authorize('interact-with-project', $project);

        $compound = Compound::create([
            'project_id'            => $project->id,
            'label'                 => $request->label ?? '(unkown)',
            'H_NMR_data'            => $request->H_NMR_data,
            'C_NMR_data'            => $request->C_NMR_data,
            'retention'             => $request->retention,
            'melting_point'         => $request->melting_point,
            'infrared'              => $request->infrared,
            'mass_adduct'           => $request->mass_adduct,
            'mass_measured'         => $request->mass_measured,
            'mass_calculated'       => $request->mass_calculated,
            'alpha_sign'            => $request->alpha_sign,
            'alpha_value'           => $request->alpha_value,
            'alpha_concentration'   => $request->alpha_concentration,
            'alpha_solvent'         => $request->alpha_solvent,
            'notes'                 => $request->notes,
            'molfile'               => $request->molfile,
            'molweight'             => $request->molweight,
            'formula'               => $request->formula,
            'exact_mass'            => $request->exact_mass,
        ]);

        $compound->toMolfile()->toSVG();

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

        $this->authorize('interact-with-project', $project);

        $compound = Compound::create([
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

        return redirect('/');
    }

    public function update(Compound $compound, Request $request)
    {
        $this->authorize('interact-with-compound', $compound);
        
        $compound->update([$request->column => $request->value]);
        $compound->makeHidden(['owner', 'project']);
        
        return response()->json($compound, 201);
    }

    public function updateAll(Compound $compound, Request $request)
    {
        $this->authorize('interact-with-compound', $compound);

        $compound->label = $request->label;
        $compound->project_id = $request->project_id ?? $compound->project_id;
        $compound->H_NMR_data = $request->H_NMR_data;
        $compound->C_NMR_data = $request->C_NMR_data;
        $compound->retention = $request->retention;
        $compound->melting_point = $request->melting_point;
        $compound->infrared = $request->infrared;
        $compound->mass_measured = $request->mass_measured;
        $compound->mass_calculated = $request->mass_calculated;
        $compound->mass_adduct = $request->mass_adduct;
        $compound->alpha_sign = $request->alpha_sign;
        $compound->alpha_value = $request->alpha_value;
        $compound->alpha_concentration = $request->alpha_concentration;
        $compound->alpha_solvent = $request->alpha_solvent;
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
        $this->authorize('interact-with-compound', $compound);

        $compound->delete();

        return redirect('/compounds');
    }

    public function confirmDelete(Compound $compound)
    {
        $this->authorize('interact-with-compound', $compound);
        
        return view('compounds.confirmdelete', compact('compound'));
    }

    protected function orderByColumn($request)
    {
        return $request->by ?? 'created_at';
    }

    protected function orderByMethod($request)
    {
        return $request->order ?? 'desc';
    }
}
