<?php

namespace App\Http\Controllers;

use App\Compound;
use Illuminate\Http\Request;

class CompoundController extends Controller
{
    function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if($compounds = auth()->user()->compounds()->orderBy('created_at', 'desc')->get());

        return view('compounds.index', compact('compounds'));
    }

}
