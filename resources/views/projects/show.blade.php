@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> {{ $project->name }} ({{ $project->compounds()->count() }} compounds) </h1>
        <p style="font-style: italic;">
          {{ $project->description }}
        </p>
    </div>

    <div style="margin-bottom: 20px;">
      <a href="/projects/{{ $project->id }}/edit"> edit </a>
      @if ($project->compounds()->count() < 1)
        | 
        <a href="/projects/{{ $project->id }}/delete" class="text-danger">delete empty project</a>
      @else
        | 
        <a href="/projects/{{ $project->id }}/export"> export all </a> 
        @if (Auth::user()->projects()->count() > 1)
        | 
        <a href="/projects/{{ $project->id }}/move">move all to other project</a>
        @endif
      @endif
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
