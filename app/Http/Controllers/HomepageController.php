<?php

namespace App\Http\Controllers;

class HomepageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function __invoke()
    {
        return redirect()->route('compounds.index');
    }
}
