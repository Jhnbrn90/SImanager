<?php

namespace App\Http\Controllers;

use App\User;
use App\Project;
use Illuminate\Http\Request;

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
        if(auth()->id() !== $project->user_id) {
            // check if the user is a supervisor of this student
            if(!$this->isSupervisorOf($project->user_id)) {
                return redirect('/');
            }
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
        if(auth()->id() !== $project->user_id) {
            // check if the user is a supervisor of this student
            if(!$this->isSupervisorOf($project->user_id)) {
                return redirect('/');
            }
        }

        return view('projects.edit', compact('project'));
    }

    public function update(Project $project, Request $request)
    {
        if(auth()->id() !== $project->user_id) {
            // check if the user is a supervisor of this student
            if(!$this->isSupervisorOf($project->user_id)) {
                return redirect('/');
            }
        }

        $project->name = $request->name;
        $project->description = $request->description;
        $project->save();

        return redirect('/projects/'.$project->id);
    }

    public function destroy(Project $project)
    {
         if(auth()->id() !== $project->user_id) {
             // check if the user is a supervisor of this student
             if(!$this->isSupervisorOf($project->user_id)) {
                 return redirect('/');
             }
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
        if(auth()->id() !== $project->user_id) {
            // check if the user is a supervisor of this student
            if(!$this->isSupervisorOf($project->user_id)) {
                return redirect('/');
            }
        }

        $project->load('compounds');
        $compounds = $project->compounds;

        return view('projects.export', compact('project', 'compounds'));
    }

    public function move(Project $project)
    {
        if(auth()->id() !== $project->user_id) {
            // check if the user is a supervisor of this student
            if(!$this->isSupervisorOf($project->user_id)) {
                return redirect('/');
            }
        }

        $project->load('compounds');
        $compounds = $project->compounds;

        $projects = auth()->user()->projects;

        return view('projects.move', compact('project', 'projects', 'compounds'));   
    }

    public function moveCompounds(Project $project, Request $request)
    {
        if(auth()->id() !== $project->user_id) {
            // check if the user is a supervisor of this student
            if(!$this->isSupervisorOf($project->user_id)) {
                return redirect('/');
            }
        }

        $project->compounds()->update(['project_id' => $request->toProject]);

        if ($request->deleteProject == "on") {
            $project->delete();
        }

        return redirect('/projects');
    }

    public function isSupervisorOf($user)
    {
        if($this->isAdmin()) {
            return true;
        }

        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        return $user->supervisors->contains(auth()->user());
    }


    public function isAdmin()
    {
        return in_array(auth()->user()->email, config('app.admins'));
    }
}
