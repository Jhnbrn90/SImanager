@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> Move every project in <em> {{ $bundle->name }} </em> </h1>
    </div>

    <div style="margin-bottom: 20px;">
      <form action="/bundle-projects/{{ $bundle->id }}" method="POST">
        {{ csrf_field() }}
        {{ method_field('patch') }}

      to 
      <div class="form-group" style="width: 20%; display:inline-block; margin-right:10px;">
        <select class="form-control" name="toBundle">
          @foreach ($bundles as $userbundle)
            @if ($userbundle->id === $bundle->id)
            @else
            <option value="{{ $userbundle->id }}">{{ $userbundle->name }}</option>
            @endif
          @endforeach
        </select>
      </div>

      <div class="checkbox" style="display:inline-block; margin-right:12px;">
        <label>
          <input type="checkbox" name="deleteBundle"> also delete "{{ $bundle->name }}"
        </label>
      </div>

      <button type="submit" class="btn btn-success">Save</button>
      </form>
    </div>
    
    <hr>

    <div>
      <h4>This bundle contains the following projects:</h4> 
      @forelse ($bundle->projects as $project)  
        <strong style="display:inline-block;"> {{ $project->name }} </strong>
        <br>
      @empty
        No projects in this bundle. 
      @endforelse
    </div>
    
</div>
@endsection
