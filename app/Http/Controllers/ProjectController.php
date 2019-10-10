<?php

namespace App\Http\Controllers;

use App\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $projects = auth()->user()->projects;

        $students = auth()->user()->students()->with('projects.compounds')->get();

        return view('projects.index', compact('projects', 'students'));
    }

    public function show(Project $project)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        $project->load('compounds');

        return view('projects.show', compact('project'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required',
        ]);

        $project = Project::create([
            'name'          => $request->name,
            'description'   => $request->description,
            'user_id'       => auth()->id(),
        ]);

        return redirect('/projects');
    }

    public function edit(Project $project)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        return view('projects.edit', compact('project'));
    }

    public function update(Project $project, Request $request)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        $project->name = $request->name;
        $project->description = $request->description;
        $project->save();

        return redirect('/projects/'.$project->id);
    }

    public function destroy(Project $project)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        // check if the project is empty
        if ($project->compounds()->count() > 0) {
            return redirect('/projects');
        }

        $project->delete();

        return redirect('/projects');
    }

    public function export(Project $project)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        $project->load('compounds');
        $compounds = $project->compounds;

        return view('projects.export', compact('project', 'compounds'));
    }

    public function move(Project $project)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        $project->load('compounds');
        $compounds = $project->compounds;

        $projects = auth()->user()->projects;

        return view('projects.move', compact('project', 'projects', 'compounds'));
    }

    public function moveCompounds(Project $project, Request $request)
    {
        if (Gate::denies('interact-with-project', $project)) {
            return redirect('/');
        }

        $project->compounds()->update(['project_id' => $request->toProject]);

        if ($request->deleteProject == 'on') {
            $project->delete();
        }

        return redirect('/projects');
    }
}
