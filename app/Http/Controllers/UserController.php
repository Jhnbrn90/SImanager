<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function updateLabel(Request $request)
    {
        $request->validate(['prefix' => 'required']);

        auth()->user()->update(['prefix' => $request->prefix]);

        return redirect()->back();
    }

    public function impersonate($userId)
    {
        $user = User::findOrFail($userId);

        $this->authorize('can-impersonate-user', $user);

        Auth::user()->setImpersonating($user->id);

        return redirect('/');
    }

    public function stopImpersonate()
    {
        Auth::user()->stopImpersonating();

        return redirect('/');
    }
}
