<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updateLabel(Request $request)
    {
        $request->validate(['prefix' => 'required']);

        auth()->user()->update(['prefix' => $request->prefix]);

        return redirect()->back();
    }
}
