<?php

namespace App\Http\Controllers;

use App\Bundle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BundleProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(Bundle $bundle)
    {
        if (Gate::denies('interact-with-bundle', $bundle)) {
            return abort(403, 'This action is not authorized.');
        }

        $projects = $bundle->projects;

        $bundles = auth()->user()->bundles;

        return view('bundle-projects.edit', compact('bundle', 'projects', 'bundles'));   
    }

    public function update(Bundle $bundle, Request $request)
    {
        if (Gate::denies('interact-with-bundle', $bundle)) {
            return abort(403, 'This action is not authorized.');
        }

        $targetBundle = Bundle::findOrFail($request->toBundle);

        if ($targetBundle->user->id !== auth()->id()) {
            return abort(403, 'You can not add projects to another user\'s bundle');
        }

        $bundle->projects()->update(['bundle_id' => $request->toBundle]);

        if ($request->deleteBundle == "on") {
            $bundle->delete();
        }

        return redirect('/projects');
    }
}
