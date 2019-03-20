@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> Projects </h1>
    </div>
    
    <div class="buttons" style="margin-bottom: 20px;">
      <div style="display:inline-block; margin-right: 10px;">
        <a href="/projects/create" class="btn btn-primary">Create new project</a>
      </div>

      <div style="display:inline-block;">
        <a href="/bundles/new" class="btn btn-warning">Create new bundle</a>
      </div>
    </div>
    
    <div>
      <div class="list-group">
        @foreach ($bundles as $bundle)
        <li class="list-group-item list-group-item-warning">
            <a href="/bundle-projects/{{ $bundle->id }}/edit"><strong>{{ $bundle->name }}</strong></a>
        </li>
        @foreach ($bundle->projects as $project)
          <a href="/projects/{{ $project->id }}" class="list-group-item">
            <div>
              <strong>{{ $project->name }} </strong>
            </div>
            <div>
              <em> {{ $project->description }} </em>
            </div>
          </a>
        @endforeach
        @endforeach
      </div>

    </div>
</div>
@endsection
