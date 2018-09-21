@extends ('layouts.master')

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
@endsection

@section ('content')

@include('layouts.navbar')

<center>
        <img src="/{{ $compound->SVGPath }}" width="143">
        
        <hr>
        
        <h1> {{ $compound->label }} </h1>

        <h4>{!! $compound->formattedFormula !!}</h4>

        <div 
            id="SI-text" 
            class="SI-text" 
            style="width: 50%; border: 1px solid #606060; border-radius: 2px; border-color:black; padding: 10px;"
        >
         <strong>R<sub>F</sub></strong> = {{ $compound->retention }}. 
         
         <span style="color:red;">[NMR data goes here]</span>. 

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
            <strong>M.p.</strong>: {{ $compound->melting_point }} &deg; C.
        @endif
        </div>
        
        <br>
        
        
        <button class="btn btn-primary copy-btn" data-clipboard-target="#SI-text" style="margin-right: 10px;">Copy text</button>
        <button class="btn btn-info">Edit info</button>

        <br><br>

        test

</center>

@endsection

@section('scripts')
<script type="text/javascript">
    var clipboard = new ClipboardJS('.copy-btn');

    clipboard.on('success', function(e) {
        alert('Copied to clipboard!');
    });
</script>
@endsection
