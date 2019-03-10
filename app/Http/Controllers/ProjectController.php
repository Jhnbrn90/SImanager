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
        $projects = auth()->user()->projects;

        $bundles = auth()->user()->bundles()->with('projects')->get();
        
        $students = auth()->user()->students()->with('bundles.projects')->get();

        return view('projects.index', compact('bundles', 'projects', 'students'));
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
        $bundles = Bundle::where('user_id', auth()->id())->get();
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
             return abort(403, 'You are not authorized to perform this action.');
         }

         // check if the project is empty 
         if ($project->compounds()->count() > 0) {
            return abort(422, 'Only empty projects can be deleted.');
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
}
