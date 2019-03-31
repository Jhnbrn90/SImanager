@extends ('layouts.master')

@section ('content')
@include('layouts.navbar')

<div class="container">
    <h1>Search results ({{ collect($structures)->count() }})</h1>

    <a href="/database/substructure">Edit query</a> or

    <a href="/database/substructure/new">New Query</a>

    <hr> @if($structures == []) No search results. @endif

    <!-- <div class="container" style="margin-bottom: 20px; margin:auto; background-color: #fff; width:100%; border-radius:10px;"> -->
    <center>
        @foreach ($structures as $structure)
        <div style="display: inline-block; margin: 12px;">
            <a href="/database/chemicals/{{ $structure->structurable->id }}" class="block" taget="_blank">
                <picture class="structure block"> {!! $structure->svg ?? '' !!} </picture>
                <strong> {{ $structure->structurable->name }} </strong>
            </a>
            <div>Quant.: {{ $structure->structurable->quantity }}</div>
            <div>({{ $structure->structurable->location }}.{{ $structure->structurable->cabinet }}.{{ $structure->structurable->number }})</div>
        </div>
        @endforeach
    </center>
    <!-- </div> -->
</div>

@endsection
