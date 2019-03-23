<?php

namespace App\Http\Controllers;

use App\Bundle;
use Illuminate\Http\Request;

class BundleProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function edit(Bundle $bundle)
    {
        $this->authorize('interact-with-bundle', $bundle);

        $projects = $bundle->projects;

        $bundles = auth()->user()->bundles;

        return view('bundle-projects.edit', compact('bundle', 'projects', 'bundles'));
    }

    public function update(Bundle $bundle, Request $request)
    {
        $this->authorize('interact-with-bundle', $bundle);

        $targetBundle = Bundle::findOrFail($request->toBundle);

        if (auth()->user()->isNot($targetBundle->owner)) {
            return abort(403, 'You can not add projects to another user\'s bundle');
        }

        $bundle->projects()->update(['bundle_id' => $request->toBundle]);

        if ($request->deleteBundle == 'on') {
            $bundle->delete();
        }

        return redirect('/projects');
    }
}
