@extends ('layouts.master')

@section('title')
    {{ $project->name }} ({{ config('app.name') }})
@endsection

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
@endsection


@section ('content')

@include('layouts.navbar')
    <center>
        <h2> {{ $project->name }} </h2>
        <a href="/projects/{{ $project->id }}">back to project</a>
        <br><br>
        <button id="copy-btn" class="btn btn-primary copy-btn" data-clipboard-target="#SI-text" style="margin-right: 10px;">Copy text block</button>
        <br>
        <br>
            <div id="SI-text" 
                class="SI-text" 
                style="width: 80%; text-align:left; border: 1px solid #606060; border-radius: 2px; border-color:black; padding: 10px;"
            >
        @foreach($compounds as $compound)
            <strong> {{ $compound->label }} </strong>
            <br>
             @if ($compound->retention)
                <strong>R<sub>F</sub></strong> = {{ $compound->retention }}. 
             @endif

             @if ($compound->H_NMR_data)
                 {!! $compound->formattedProtonNMR() !!}
             @endif

             @if ($compound->C_NMR_data)
                 {!! $compound->formattedCarbonNMR() !!}
             @endif

            @if ($compound->infrared && $compound->infrared !== "@")
                <strong>IR (neat):</strong> &nu;max (cm<sup>-1</sup>): {{ $compound->infrared }}.
            @endif
            
            @if ($compound->mass_measured && $compound->mass_measured !== "@")
                <strong>HRMS (ESI)</strong>: <em>m/z</em> calculated for {!! $compound->formattedFormulaForHRMS() !!} = {{ $compound->mass_calculated }}, found = {{ $compound->mass_measured }}. 
            @endif

            @if ($compound->alpha_value && $compound->alpha_value !== "@")
                <strong>[&alpha;]<sup>20</sup><sub>D</sub></strong> = {{ $compound->alpha_sign }} {{ $compound->alpha_value }} (c = {{ $compound->alpha_concentration }}, {!! $compound->formattedAlphaSolvent() !!}). 
            @endif

             @if ($compound->melting_point && $compound->melting_point !== "@")
                <strong>M.p.</strong>: {{ $compound->melting_point }} &deg;C.
            @endif
            <br><br>
        @endforeach
            </div>
            </div>

    </center>
@endsection

@section('scripts')
<script type="text/javascript">
    var clipboard = new ClipboardJS('.copy-btn');

    clipboard.on('success', function(e) {
        document.querySelector('#copy-btn').innerHTML = 'Copied to clipboard!';
        document.querySelector('#copy-btn').classList.add('btn-success');

        setTimeout(() => {
            document.querySelector('#copy-btn').innerHTML = 'Copy text';
            document.querySelector('#copy-btn').classList.remove('btn-success');
        }, 2000);

        e.clearSelection();
    });

</script>
@endsection
