@extends ('layouts.master')

@section('title')
    {{ $compound->label }} ({{ config('app.name') }})
@endsection

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
@endsection


@section ('content')

@include('layouts.navbar')

<center>
        <img src="/{{ $compound->SVGPath }}" width="120">
        
        <hr>
        
        <h2> {{ $compound->label }} </h2>

        <br>
        

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

        <button id="copy-btn" class="btn btn-primary copy-btn" data-clipboard-target="#SI-text" style="margin-right: 10px;">Copy text</button>
        <a class="btn btn-info" href="/compounds/{{ $compound->id }}/edit">Edit info</a>
        

        <br><br>
        <h3>Collected data</h3>
        <br>
    </center>

        <center>
            <div class="container" style="width:70%;">
            <table class="table table-striped table-hover">
                <tr>
                    <td><strong>Label</strong></td>
                    <td>{{ $compound->label }}</td>
                </tr>

                <tr>
                    <td><strong>Notes</strong></td>
                    <td>{{ $compound->notes }}</td>
                </tr>

                <tr>
                    <td><strong>Formula</strong></td>
                    @if ($compound->formula)
                        <td>{!! $compound->formattedFormula !!}</td>
                    @else
                        <td><a href="/compounds/{{ $compound->id }}/edit">&plus; add structure</a></td>
                    @endif
                </tr>

                <tr>
                    <td><strong>Molecular weight</strong></td>
                    <td>{{ $compound->molweight }}</td>
                </tr>

                <tr>
                    <td><strong>Exact mass</strong></td>
                    <td>{{ $compound->exact_mass }}</td>
                </tr>

                <tr>
                    <td><strong>R<sub>F</sub></strong></td>
                    <td>{{ $compound->retention }}</td>
                </tr>

                <tr>
                    <td><strong>IR</strong></td>
                    <td>{{ $compound->infrared == "@" ? 'unobtainable' : $compound->infrared == "" ? 'N.D.' : $compound->infrared }}</td>
                </tr>

                <tr>
                    <td><strong>Melting Point</strong></td>
                    <td>
                        @if($compound->melting_point == "@")
                            (unobtainable)
                        @elseif($compound->melting_point == "")
                            (not yet determined)
                        @else 
                            {{ $compound->melting_point }} &deg; C.
                        @endif
                    </td>
                </tr>

                <tr>
                    <td><strong>HRMS</strong></td>
                    <td>
                        @if ($compound->mass_measured == "@")
                            (unobtainable)
                        @elseif($compound->mass_measured =="")
                            (not yet determined)
                        @else
                            {!! $compound->formattedFormulaForHRMS() !!} <br>
                            calculated = {{ $compound->mass_calculated }},
                            found = {{ $compound->mass_measured }}
                        @endif
                    </td>
                </tr>

                 <tr>
                    <td><strong>[&alpha;]<sup>20</sup><sub>D</sub></strong></td>
                    <td>
                        @if ($compound->alpha_value == "@")
                            (unobtainable)
                        @elseif($compound->alpha_value =="")
                            (not yet determined)
                        @else
                            {{ $compound->alpha_sign }}
                            {{ $compound->alpha_value }}
                            (c = {{ $compound->alpha_concentration }}, {!! $compound->formattedAlphaSolvent() !!})
                        @endif
                    </td>
                </tr>
            </table>
            <br><br>
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
