<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function __invoke()
    {
        return redirect()->route('compounds.index');
    }
}
