<?php

namespace App\Http\Controllers;

use App\Bundle;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create()
    {
        return view('bundles.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);

        Bundle::create([
            'name'          => $request->name,
            'description'   => $request->description,
            'user_id'       => auth()->id(),
        ]);

        return redirect('/projects');
    }
}
