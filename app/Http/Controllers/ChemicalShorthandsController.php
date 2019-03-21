<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChemicalShorthandsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function index()
    {
        $shorthands = DB::table('shorthands')->get();

        return view('database.shorthands.index', compact('shorthands'));
    }

    public function edit($shorthandId)
    {
        $shorthand = DB::table('shorthands')->where('id', $shorthandId);

        if($shorthand->count() == 0) {
            return back();
        }
        
        $result = $shorthand->first();

        return view('database.shorthands.edit', compact('result'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'shorthand' => 'required|unique:shorthands,shorthand',
            'cas'       => 'required'
        ]);

        DB::table('shorthands')->insert([
            'shorthand' => $request->shorthand,
            'cas'       => $request->cas
        ]);

        session()->flash('message', 'Created the new shorthand.');

        return redirect('/database/shorthands');
    }

    public function update(Request $request, $shorthandId)
    {
        $request->validate([
            'shorthand' => 'required',
            'cas'       => 'required'
        ]);

        $shorthand = DB::table('shorthands')->where('id', $shorthandId);
        if($shorthand->count() == 0) {
            return back();
        }

        $shorthand->update([
            'shorthand' => $request->shorthand,
            'cas'       => $request->cas
        ]);

        session()->flash('message', 'Succesfully updated the shortname.');

        return redirect('/database/shorthands');
    }

    public function destroy($shorthandId)
    {
        $shorthand = DB::table('shorthands')->where('id', $shorthandId);

        if($shorthand->count() == 0) {
            return back();
        }

        $shorthand->delete();
        
        session()->flash('message', 'Succesfully deleted shortname.');

        return back();
    }
}
