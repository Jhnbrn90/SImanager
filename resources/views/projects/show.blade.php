@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> {{ $project->name }} </h1>
    </div>

    <div style="margin-bottom: 20px;">
      change name | export | delete project
    </div>
    
    <hr>

    <div>
      <h4>This project contains the following compounds:</h4> 
      @forelse ($project->compounds as $compound)  
        <img src="/{{ $compound->SVGPath }}" height="65">
        <strong style="display:inline-block;"> {{ $compound->label }} </strong>
        <br>
      @empty
        No compounds in this project. 
      @endforelse
    </div>
    
</div>
@endsection
