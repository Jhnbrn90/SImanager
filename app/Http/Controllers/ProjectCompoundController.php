<?php

namespace App\Http\Controllers;

use App\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectCompoundController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function edit(Project $project)
    {
        $this->authorize('interact-with-project', $project);

        $project->load('compounds');
        $compounds = $project->compounds;

        $projects = auth()->user()->projects;

        return view('project-compounds.edit', compact('project', 'projects', 'compounds'));   
    }

    public function update(Project $project, Request $request)
    {
        $this->authorize('interact-with-project', $project);

        $targetProject = Project::findOrFail($request->toProject);

        if (auth()->user()->isNot($targetProject->owner)) {
            return abort(403, 'You can not add compounds to another user\'s library');
        }

        $project->compounds()->update(['project_id' => $request->toProject]);

        if ($request->deleteProject == "on") {
            $project->delete();
        }

        return redirect('/projects');
    }
}
