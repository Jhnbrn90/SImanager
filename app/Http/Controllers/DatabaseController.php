<?php

namespace App\Http\Controllers;

use App\Chemical;
use Illuminate\Http\Request;
use App\Helpers\ChemicalsDatabase\ChemicalFinder;

class DatabaseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'impersonate']);
    }

    public function index()
    {
        return view('database.index');
    }

    public function search(Request $request)
    {
        $searchQuery = $request->search;

        // if no input fields were provided, redirect back
        if ($searchQuery == null) {
            return back();
        }

        // Did the user want to search by 'remarks' ?
        if (preg_match('/remark/', strtolower($searchQuery))) {
            return $this->searchRemarks($searchQuery);
        }

        // Did the user enter a CAS no?
        if (preg_match('/[1-9]{1}[0-9]{1,5}-\d{2}-\d/', $searchQuery)) {
            return $this->searchCas($searchQuery);
        }

        // Did the user enter a string that is shorter than 10 characters?
        // If so, run the query quickly through to the shorthand table

        if (strlen($searchQuery) <= 10) {
            if ($cas = (new ChemicalFinder($searchQuery))->lookupShorthand()) {
                $chemicals = Chemical::where('cas', $cas)->get();

                if ($chemicals->count() === 0) {
                    session()->flash('message', 'No results for: '.$request->search);

                    return redirect('/database');
                }

                return view('database.show', compact('chemicals'));
            }
        }

        // If the shorthand table did not resolve our request
        // We try our longer name version, otherwise after all of this we'll contact 3rd party APi's

        $chemicals = Chemical::where('name', 'like', $request->search)->get();

        if ($chemicals->count() === 0) {
            // run the name through external API's
            $cas = (new ChemicalFinder($request->search))->getCas();

            // Did the ChemicalFinder class find a hit?
            // If it did not, return early
            if ($cas == null) {
                session()->flash('error', '"'.$request->search.'" is not in the database.');

                return redirect('/database');
            }

            // If it did, return the chemicals and perform the redirect
            $chemicals = Chemical::where('cas', $cas)->get();

            return view('database.show', compact('chemicals'));
        }

        // If it did find some hits in the database, redirect
        return view('database.show', compact('chemicals'));
    }

    protected function searchRemarks($input)
    {
        $remark = preg_replace('/remark\s?\:?\s?/', '', strtolower($input));

        $chemicals = Chemical::where('remarks', 'like', '%'.$remark.'%')->get();

        if ($chemicals->count() === 0) {
            session()->flash('message', 'No results for your remark: '.$remark);

            return redirect('/database');
        }

        return view('database.show', compact('chemicals'));
    }

    protected function searchCas($cas)
    {
        $chemicals = Chemical::where('cas', $cas)->get();

        if ($chemicals->count() === 0) {
            session()->flash('message', 'No results for CAS number: '.$request->search);

            return redirect('/database');
        }

        return view('database.show', compact('chemicals'));
    }
}
