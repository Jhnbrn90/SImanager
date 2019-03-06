<?php

namespace App\Http\Controllers;

use App\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectCompoundController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(Project $project)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        $project->load('compounds');
        $compounds = $project->compounds;

        $projects = auth()->user()->projects;

        return view('project-compounds.edit', compact('project', 'projects', 'compounds'));   
    }

    public function update(Project $project, Request $request)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        $targetProject = Project::findOrFail($request->toProject);

        if ($targetProject->user->id !== auth()->id()) {
            return abort(403, 'You can not add compounds to another user\'s library');
        }

        $project->compounds()->update(['project_id' => $request->toProject]);

        if ($request->deleteProject == "on") {
            $project->delete();
        }

        return redirect('/projects');
    }
}
