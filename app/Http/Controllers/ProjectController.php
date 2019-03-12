<?php

namespace App\Http\Controllers;

use App\User;
use App\Bundle;
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
        $bundles = auth()->user()->bundles()->with('projects')->get();
        
        $students = auth()->user()->students()->with('bundles.projects')->get();

        return view('projects.index', compact('bundles', 'students'));
    }

    public function show(Project $project)
    {
        Gate::authorize('interact-with-project', $project);

        $project->load('compounds');

        return view('projects.show', compact('project'));
    }

    public function create()
    {
        $bundles = auth()->user()->bundles;

        return view('projects.create', compact('bundles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required',
            'bundle_id' => 'required|exists:bundles,id'
        ]);

        $project = Project::create([
            'name'          =>  $request->name,
            'description'   =>  $request->description,
            'user_id'       =>  auth()->id(),
            'bundle_id'     => $request->bundle_id,
        ]);

        return redirect('/projects');
    }

    public function edit(Project $project)
    {
        Gate::authorize('interact-with-project', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(Project $project, Request $request)
    {
        Gate::authorize('interact-with-project', $project); 

        $request->validate(['name' => 'required']);

        $project->update($request->only('name', 'description'));

        return redirect('/projects/'.$project->id);
    }

    public function destroy(Project $project)
    {
         Gate::authorize('interact-with-project', $project);

         if (! $project->isEmpty()) {
            return abort(422, 'Only empty projects can be deleted.');
         }

         $project->delete();

         return redirect('/projects');
    }

    public function export(Project $project)
    {
        Gate::authorize('interact-with-project', $project);

        $project->load('compounds');
        $compounds = $project->compounds;

        return view('projects.export', compact('project', 'compounds'));
    }
}
