@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title" style="margin-bottom: 25px;">
        <h1> Create new bundle </h1>
    </div>

    <div style="margin-bottom: 20px;">
      <form action="/bundles" method="POST" autocomplete="off">
        {{ csrf_field() }}
        <div class="form-group" style="width:50%;">
          <label for="name">Bundle name</label>
          <input type="text" id="name" class="form-control" name="name" placeholder="Bundle name">
        </div>
        
        <div class="form-group" style="width:50%;">
          <label for="description">Bundle description</label>
          <input type="text" id="description" class="form-control" name="description" placeholder="Bundle description.">
        </div>
        
        <div class="form-group" style="width:50%">
          <button type="submit" class="btn btn-success btn-block"> Save </button>
        </div>

      </form>
    
</div>
@endsection
