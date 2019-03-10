<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharingDataController extends Controller
{
    public function addSupervisor()
    {
        $users = User::all();

        $supervisors = auth()->user()->supervisors;

        return view('supervisor.add', compact('users', 'supervisors'));
    }

    public function store(Request $request)
    {
        $supervisor = User::findOrFail($request->supervisor);

        auth()->user()->addSupervisor($supervisor);

        return redirect('/supervisor/add');
    }

    public function listStudents()
    {
        if (auth()->user()->isAdmin()) {
            $students = User::all();
        }

        $students = auth()->user()->students;

        return view('students.list', compact('students'));
    }
}
