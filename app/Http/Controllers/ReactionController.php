<?php

namespace App\Http\Controllers;

use App\Project;
use App\Compound;
use App\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');    
    }

    public function index()
    {
        $user = auth()->user();
        $projects = $user->projects()
                    ->orderBy('id', 'desc')
                    ->with('reactions')
                    ->get();

        return view('reactions.index', compact('user', 'projects'));
    }

    public function show(Reaction $reaction)
    {
        if (Gate::denies('interact-with-reaction', $reaction)) {
            return redirect('/reactions');
        }

        return view('reactions.create', compact('reaction'));
    }

    public function store(Project $project)
    {
        $reaction = Reaction::create([
            'project_id' => $project->id, 
            'user_id' => $project->user->id,
            'label' => auth()->user()->newReactionLabel,
        ]);

        return view('reactions.create', compact('reaction'));
    }

    public function update(Request $request, Reaction $reaction)
    {
        if (Gate::denies('interact-with-reaction', $reaction)) {
            return redirect('/reactions');
        }

        $project = Project::findOrFail($request->project);

        if ($request->type == 'product') { 
            $compound = Compound::create([
                'user_id'               => $project->user_id,
                'project_id'            => $project->id,
                'label'                 => $reaction->nextProductLabel(),
                'molfile'               => $request->molfile,
                'molweight'             => $request->molweight,
                'formula'               => $request->formula,
                'exact_mass'            => $request->exact_mass,
            ]);

            $compound->toMolfile()->toSVG();

            $reaction->compounds()->attach($compound, ['type' => 'product']);
        }

        return redirect('/reactions/' . $reaction->id);
    }
}
