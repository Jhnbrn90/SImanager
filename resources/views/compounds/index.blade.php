@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container-mx-auto">
    <div class="title">
        <h1> Compounds </h1>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>Label</th>
                    <th colspan="2">NMR</th>
                    <th>R <sub>F</sub></th>
                    <th>IR</th>
                    <th>MP</th>
                    <th colspan="2">HRMS</th>
                    <th colspan="3">Specific Rotation</th>
                    <th>Notes</th>
                </tr>
                <tr>
                    <td> &nbsp; </td>
                    <td> &nbsp; </td>
                    <td><sup>1</sup>H</td>
                    <td><sup>13</sup>C</td>
                    <td> &nbsp; </td>
                    <td> &nbsp; </td>
                    <td> °C </td>
                    <td>Adduct</td>
                    <td>Found Mass</td>
                    <td>[α]</td>
                    <td>c</td>
                    <td>Solvent</td>
                </tr>
            </thead>

            <tbody>

                @forelse ($compounds as $compound)
                    <tr>
                        <td><img src="{{ $compound->SVGPath }}" height="70" style="margin-top:-10px;"></td>
                        
                        <td><a href="/compounds/{{ $compound->id }}">{{ $compound->label }}</a></td>
                        
                        <td class="{{ $compound->proton_nmr ? 'bg-success' : '' }}">
                            <input type="checkbox" {{ $compound->proton_nmr ? 'checked' : '' }} disabled>
                        </td>
                        
                        <td class="{{ $compound->proton_nmr ? 'bg-success' : '' }}">
                            <input type="checkbox" {{ $compound->carbon_nmr ? 'checked' : '' }} disabled>
                        </td>

                        <td class="{{ $compound->retention ? 'bg-success' : '' }}">
                            {{ $compound->retention }}
                        </td>

                        <td class="{{ $compound->infrared ? 'bg-success' : '' }}">
                            <ir-field data="{{ $compound->infrared }}"></ir-field>
                        </td>

                        @if ($compound->melting_point == "@")
                            <td style="background-color: #F8F8F8"></td>
                        @else
                            <td class="{{ $compound->melting_point ? 'bg-success' : '' }}">
                                {{ $compound->melting_point  }}
                            </td>
                        @endif

                        @if ($compound->mass_adduct == "@")
                             <td style="background-color: #F8F8F8"></td>
                        @else
                             <td class="{{ $compound->mass_adduct ? 'bg-success' : '' }}">
                                {{ $compound->mass_adduct }}
                            </td>
                        @endif

                        @if ($compound->mass_measured == "@")
                            <td style="background-color: #F8F8F8"></td>
                        @else 
                            <td class="{{ $compound->mass_measured ? 'bg-success' : ''}}">
                                {{ $compound->mass_measured }}
                            </td>
                        @endif
                        
                        @if ($compound->alpha_value == "@")
                            <td style="background-color: #F8F8F8"></td>
                            <td style="background-color: #F8F8F8"></td>
                            <td style="background-color: #F8F8F8"></td>
                        @else
                            <td class="{{ $compound->alpha_sign ? 'bg-success' : '' }}">
                                {{ $compound->alpha_sign }} {{ $compound->alpha_value }}
                            </td>

                            <td class="{{ $compound->alpha_concentration ? 'bg-success' : '' }}">
                                {{ $compound->alpha_concentration }} 
                            </td>

                            <td class="{{ $compound->alpha_solvent ? 'bg-success' : '' }}">
                                {{ $compound->alpha_solvent }}
                            </td>
                        @endif

                        <td>
                            {{ $compound->notes }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14">
                            <br>
                            Hey there {{ Auth::user()->name }}!
                            <br>
                            It looks like you don't have any compounds yet.
                            <br>
                            <a href="/compounds/new"><strong>Click here to add your first</strong></a>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>
    </div>

</div>

@endsection
