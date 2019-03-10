@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> 
          {{ $project->name }} 
          ({{ $count = $project->compounds()->count() }} {{ str_plural('compound', $count) }}) 
        </h1>
        <p style="font-style: italic;">
          {{ $project->description }}
        </p>
    </div>

    <div style="margin-bottom: 20px;">
      <a class="btn btn-primary" style="margin-right: 10px;" href="/projects/{{ $project->id }}/edit">
        Edit project 
      </a>
      @if ($project->compounds()->count() < 1)
        <form action="/projects/{{ $project->id }}" method="POST" style="display: inline-block;">
          {{ csrf_field() }}
          {{ method_field('DELETE') }}
          <button type="submit" style="margin-right: 10px;" class="btn btn-danger">
            Delete this project
          </button>
        </form>
      @else
        <a href="/projects/{{ $project->id }}/export" class="btn btn-default" style="margin-right: 10px;">
          Export 
        </a> 
        @if (Auth::user()->projects()->count() > 1)
        <a href="/project-compounds/{{ $project->id }}/edit" class="btn btn-default">
          Move compounds
        </a>
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
