<?php

namespace App\Http\Controllers;

use App\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = auth()->user()->projects;
        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            // check if the user is a supervisor of this user.
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
            'name'          =>  $request->name,
            'description'   =>  $request->description,
            'user_id'       =>  auth()->id(),
        ]);

        return redirect('/projects');
    }

    public function edit(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            // check if the user is a supervisor of this user.
            return redirect('/');
        }

        return view('projects.edit', compact('project'));
    }

    public function update(Project $project, Request $request)
    {
        if ($project->user_id !== auth()->id()) {
            // check if the user is a supervisor of this user.
            return redirect('/');
        }

        $project->name = $request->name;
        $project->description = $request->description;
        $project->save();

        return redirect('/projects/'.$project->id);
    }

    public function destroy(Project $project)
    {
         if ($project->user_id !== auth()->id()) {
             // check if the user is a supervisor of this user.
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
        if ($project->user_id !== auth()->id()) {
            // check if the user is a supervisor of this user.
            return redirect('/');
        }

        $project->load('compounds');
        $compounds = $project->compounds;

        return view('projects.export', compact('project', 'compounds'));
    }

    public function move(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            // check if the user is a supervisor of this user.
            return redirect('/');
        }

        $project->load('compounds');
        $compounds = $project->compounds;

        $projects = auth()->user()->projects;

        return view('projects.move', compact('project', 'projects', 'compounds'));   
    }

    public function moveCompounds(Project $project, Request $request)
    {
        if ($project->user_id !== auth()->id()) {
            // check if the user is a supervisor of this user.
            return redirect('/');
        }

        $project->compounds()->update(['project_id' => $request->toProject]);

        if ($request->deleteProject == "on") {
            $project->delete();
        }

        return redirect('/projects');
    }
}
