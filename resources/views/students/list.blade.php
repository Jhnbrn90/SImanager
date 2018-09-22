@extends('layouts.master')

@section('content')

@include('layouts.navbar')

<div class="container">
    <center>
        <div style="width:200px;">
            <h3>Students:</h3>
            
            <ul class="list-group">
                @forelse ($students as $student)
                    <li class="list-group-item">
                        <a href="/students/view/data/{{ $student->id }}">
                            {{ $student->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item">
                    No students
                </li>
                @endforelse
            </ul>

        </div>        
    </center>
</div>


@endsection
