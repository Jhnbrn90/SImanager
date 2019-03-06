@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> Move everything in <em> {{ $project->name }} </em> </h1>
    </div>

    <div style="margin-bottom: 20px;">
      <form action="/project-compounds/{{ $project->id }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('patch') }}

      to 
      <div class="form-group" style="width: 20%; display:inline-block; margin-right:10px;">
        <select class="form-control" name="toProject">
          @foreach ($projects as $userproject)
            @if ($project->id == $userproject->id)
            @else
            <option value="{{ $userproject->id }}">{{ $userproject->name }}</option>
            @endif
          @endforeach
        </select>
      </div>
      
        <div class="checkbox" style="display:inline-block; margin-right:12px;">
          <label>
            <input type="checkbox" name="deleteProject"> also delete "{{ $project->name }}"
          </label>
        </div>

      <button type="submit" class="btn btn-success">Save</button>
      </form>
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
