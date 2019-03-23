<?php

namespace App\Http\Controllers;

use App\Project;
use App\Compound;
use App\Reaction;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function index()
    {
        $projects = auth()->user()->projects()
                    ->orderBy('id', 'desc')
                    ->with('reactions')
                    ->get();

        return view('reactions.index', compact('projects'));
    }

    public function show(Reaction $reaction)
    {
        $this->authorize('interact-with-reaction', $reaction);

        return view('reactions.create', compact('reaction'));
    }

    public function store(Project $project)
    {
        $this->authorize('interact-with-project', $project);

        $reaction = Reaction::create([
            'project_id'    => $project->id,
            'label'         => auth()->user()->newReactionLabel,
        ]);

        return view('reactions.create', compact('reaction'));
    }

    public function update(Request $request, Reaction $reaction)
    {
        $this->authorize('interact-with-reaction', $reaction);

        if ($request->type == 'product') {
            $compound = Compound::create([
                'project_id'            => $reaction->project->id,
                'label'                 => $reaction->nextProductLabel(),
                'molfile'               => $request->molfile,
                'molweight'             => $request->molweight,
                'formula'               => $request->formula,
                'exact_mass'            => $request->exact_mass,
            ]);

            $compound->toMolfile()->toSVG();

            $reaction->compounds()->attach($compound, ['type' => 'product']);
        }

        return redirect('/reactions/'.$reaction->id);
    }
}
