@extends ('layouts.master')

@section ('content')
@include('layouts.navbar')

<div class="container">
    <h1>Search results ({{ collect($matches)->count() }})</h1>

    <a href="/database/substructure">Edit query</a> or

    <a href="/database/substructure/new">New Query</a>

    <hr> @if($matches == []) No search results. @endif

    <!-- <div class="container" style="margin-bottom: 20px; margin:auto; background-color: #fff; width:100%; border-radius:10px;"> -->
    <center>
        @foreach ($matches as $chemical)
        <div style="display: inline-block; margin: 12px;">
            <a href="/database/chemicals/{{ $chemical->id }}" class="block" taget="_blank">
                <picture class="structure block"> {!! $chemical->structure->svg ?? '' !!} </picture>
                <strong> {{ $chemical->name }} </strong>
            </a>
            <div>Quant.: {{ $chemical->quantity }}</div>
            <div>({{ $chemical->location }}.{{ $chemical->cabinet }}.{{ $chemical->number }})</div>
        </div>
        @endforeach
    </center>
    <!-- </div> -->
</div>

@endsection
