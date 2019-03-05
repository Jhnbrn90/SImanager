<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updateLabel(Request $request)
    {
        $request->validate(['prefix' => 'required']);
        $user = auth()->user();

        $user->prefix = $request->prefix;
        $user->save();

        return redirect()->back();
    }
}
