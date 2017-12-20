@extends ('layouts.master')

@section ('content')

@include('layouts.navbar')

<h1> Compound {{ $compound->id }} </h1>

Label: {{ $compound->label }}


@endsection
