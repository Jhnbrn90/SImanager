@extends ('layouts.master')

@section ('content')

@include('layouts.navbar')

<div class="container">
    <div class="panel panel-default">
        <h1>Edit shorthand</h1>

        <div class="container">

            <form method="POST" action="/database/shorthands/{{ $result->id }}">
                {{ csrf_field() }}
                {{ method_field('patch') }}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="shorthand">Shorthand:</label>
                        <input type="text" class="form-control" id="shorthand" name="shorthand" value="{{ $result->shorthand }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="cas">CAS:</label>
                        <input type="text" class="form-control" id="cas" name="cas" value="{{ $result->cas }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group"><br>
                        <button type="submit" class="btn btn-success">Update</button>
                    </div>
                </div>
            </div>
            </form>

        </div>
    </div>
</div>

@endsection
