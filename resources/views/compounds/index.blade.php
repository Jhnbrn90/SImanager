@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> Compounds </h1>
    </div>

    <hr>

    <div class="new-btn">
        <a href="/compounds/new">
            <center>
                <span class="glyphicon glyphicon-plus add" aria-hidden="true"></span>
                <br>
                Add new
            </center>
        </a>
    </div>

    <div class="compound">
        <a href="#" class="btn">
            <img src="/svg/1.svg">
            <span class="label">JBN478</span>
        </a>
    </div>

    <div class="compound">
        <a href="#" class="btn">
            <img src="/svg/1.svg">
            <span class="label">JBN478</span>
        </a>
    </div>

    <div class="compound">
        <a href="#" class="btn">
            <img src="/svg/1.svg">
            <span class="label">JBN478</span>
        </a>
    </div>

    <div class="compound">
        <a href="#" class="btn">
            <img src="/svg/1.svg">
            <span class="label">JBN478</span>
        </a>
    </div>

    <div class="compound">
        <a href="#" class="btn">
            <img src="/svg/1.svg">
            <span class="label">JBN478</span>
        </a>
    </div>

    <div class="compound">
        <a href="#" class="btn">
            <img src="/svg/1.svg">
            <span class="label">JBN478</span>
        </a>
    </div>

    <div class="compound">
        <a href="#" class="btn">
            <img src="/svg/1.svg">
            <span class="label">JBN478</span>
        </a>
    </div>

    @forelse ($compounds as $compound)
        <div class="compound">
            <a href="#" class="btn">
                <img src="/svg/{{ $compound->id }}.svg">
                <span class="label">{{ $compound->label }}</span>
            </a>
        </div>
    @empty
        <p>No compounds</p>
    @endforelse

</div>

@endsection
