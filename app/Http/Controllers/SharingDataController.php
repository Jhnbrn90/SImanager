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

        $supervisors = Auth::user()->supervisors;

        return view('supervisor.add', compact('users', 'supervisors'));
    }

    public function store(Request $request)
    {
        $supervisor = User::findOrFail($request->supervisor);
        Auth::user()->addSupervisor($supervisor);

        return redirect('/supervisor/add');
    }

    public function listStudents()
    {
        $students = auth()->user()->students;
        return view('students.list', compact('students'));
    }
}
