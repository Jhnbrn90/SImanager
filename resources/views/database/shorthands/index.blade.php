@extends ('layouts.master')

@section ('content')

@include('layouts.navbar')

<div class="container">
    <form method="POST" action="/database/shorthands">
        {{ csrf_field() }}
    <div class="panel panel-default" style="overflow:hidden; padding: 10px;">
        <h1>Add new shorthand</h1>
        <br>
        <div class="container">
            <div class="row">
                <div class="col-md-2 col-md-offset-3">
                    <div class="form-group">
                        <label for="shorthand">Shorthand:</label>
                        <input type="text" class="form-control" id="shorthand" name="shorthand">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="cas">CAS:</label>
                        <input type="text" class="form-control" id="cas" name="cas">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group"><br>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>

            <br>

            <h3>Existing shorthands:</h3>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <td>Shorthand</td>
                        <td>CAS</td>
                        <td></td>
                        <td></td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shorthands as $shorthand)
                    <tr>
                        <td>{{ $shorthand->shorthand }}</td>
                        <td>{{ $shorthand->cas }}</td>
                        <td><a href="/database/shorthands/{{ $shorthand->id }}/edit" class="btn btn-warning">Edit</a></td>
                        <td><a href="/database/shorthands/{{ $shorthand->id }}/delete" onclick="return confirm('Are you sure?');" class="btn btn-danger">Delete</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

    </div>
    </form>
</div>

@endsection
