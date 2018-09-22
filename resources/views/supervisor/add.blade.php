@extends('layouts.master')

@section('content')

@include('layouts.navbar')

<div class="container">
    <center>
        <h3>Add a supervisor</h3>
        
        <div class="form-group" style="width:20%;">
            <form action="/supervisor" method="POST">
                {{ csrf_field() }}
                <select name="supervisor" class="form-control">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}"> {{ $user->name }}</option>
                    @endforeach
                </select>

                <br>
                
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
        <hr>
        <h3>Current supervisors:</h3>
        <ul>
            @forelse ($supervisors as $supervisor)
                <li>{{ $supervisor->name }}</li>
            @empty
                <li>No supervisors</li>
            @endforelse
        </ul>
        

    </center>
</div>


@endsection
