@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title" style="margin-bottom: 25px;">
        <h1> Editing <em> {{ $project->name }} </em> </h1>
    </div>

    <div style="margin-bottom: 20px;">
      <form action="/projects/{{ $project->id }}" method="POST" autocomplete="off">
        {{ csrf_field() }}
        {{ method_field('patch') }}
        <div class="form-group" style="width:50%;">
          <label for="name">Project name</label>
          <input type="text" id="name" class="form-control" name="name" placeholder="Project name" value="{{ $project->name }}">
        </div>
        
        <div class="form-group" style="width:50%;">
          <label for="description">Project description</label>
          <input type="text" id="description" class="form-control" name="description" placeholder="Project description." value="{{ $project->description }}">
        </div>
        
        <div class="form-group" style="width:50%">
          <button type="submit" class="btn btn-success btn-block"> Save </button>
        </div>

      </form>
    
</div>
@endsection
