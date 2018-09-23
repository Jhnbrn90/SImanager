@extends ('layouts.master')

@section('title')
    {{ $compound->label }} ({{ config('app.name') }})
@endsection

@section ('content')

@include('layouts.navbar')

<center>
        <img src="/{{ $compound->SVGPath }}" width="120">
        
        <hr>
        
        <div class="bg-danger">
            <p style="font-size:1.7rem;" class="text-large text-danger">
                Warning! <br>
                You are about to delete this compound and all of its data.
            </p>
        </div>
        
        <h3 style="display:inline-block;">{{ $compound->label }} </h3>
        
        <!-- <a href="/compounds/{{ $compound->id }}" class="btn btn-link btn-lg">Cancel</a> -->
        <delete-compound-form 
            compound-id="{{ $compound->id }}"
            compound-label="{{ $compound->label }}"
            csrf_token="{{ csrf_token() }}"
        ></delete-compound-form>

        <br>
        <br>
        <br>

    </center> 
    
@endsection
