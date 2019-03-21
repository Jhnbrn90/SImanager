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
            <a href="/database/chemicals/{{ $chemical->id }}" taget="_blank">
                <div style="background-color: #fff; border: 1px solid black; border-radius: 8px; overflow:hidden; margin-bottom: 7px;">
                    <img src="/database/svg/{{ $chemical->id }}.svg">
                </div>
            </a>
            <strong>
                <a href="/database/chemicals/{{ $chemical->id }}" taget="_blank">
                    {{ $chemical->name }}
                </a>
            </strong>
            <br /> Quant.: {{ $chemical->quantity }}
            <br> ({{ $chemical->location }}.{{ $chemical->cabinet }}.{{ $chemical->number }})
            <br />
            <br />
        </div>
        @endforeach
    </center>
    <!-- </div> -->
</div>

@endsection
