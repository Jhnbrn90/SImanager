<?php

namespace App\Http\Controllers;

use App\Bundle;
use App\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function index()
    {
        $bundles = auth()->user()->bundles()->with('projects')->get();
        $students = auth()->user()->students()->with('bundles.projects')->get();

        return view('projects.index', compact('bundles', 'students'));
    }

    public function show(Project $project)
    {
        $this->authorize('interact-with-project', $project);

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
        $validated = $request->validate([
            'name'      => 'required',
            'bundle_id' => 'required|exists:bundles,id',
        ]);

        $bundle = Bundle::findOrFail($validated['bundle_id']);

        $this->authorize('interact-with-bundle', $bundle);

        $project = Project::create([
            'name'          =>  $request->name,
            'description'   =>  $request->description,
            'bundle_id'     => $request->bundle_id,
        ]);

        return redirect('/projects');
    }

    public function edit(Project $project)
    {
        $this->authorize('interact-with-project', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(Project $project, Request $request)
    {
        $this->authorize('interact-with-project', $project);

        $validated = $request->validate([
            'name' => 'required',
            'bundle_id' => 'required|exists:bundles,id',
        ]);

        $bundle = Bundle::findOrFail($validated['bundle_id']);

        if ($request->bundle_id !== $project->bundle_id) {
            $this->authorize('interact-with-bundle', $bundle);

            $project->moveTo($bundle);
        }

        $project->update($request->only('name', 'description'));

        return redirect('/projects/'.$project->id);
    }

    public function destroy(Project $project)
    {
        $this->authorize('interact-with-project', $project);

        if (! $project->isEmpty()) {
            return abort(422, 'Only empty projects can be deleted.');
        }

        $project->delete();

        return redirect('/projects');
    }

    public function export(Project $project)
    {
        $this->authorize('interact-with-project', $project);

        $project->load('compounds');
        $compounds = $project->compounds;

        return view('projects.export', compact('project', 'compounds'));
    }
}
