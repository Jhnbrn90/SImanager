@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<h1> Compounds </h1>

@forelse ($compounds as $compound)

    {{ $compound->label }}

    @empty

    <p>No compounds</p>

@endforelse

@endsection
