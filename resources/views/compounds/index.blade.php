@extends ('layouts.master')

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> Compounds </h1>
    </div>

    <div class="table-responsive">
        <table class="table" style="text-align: center;">
            <thead>
                <tr>
                    <th></th>
                    <th>Label</th>
                    <th colspan="2">NMR</th>
                    <th>R <sub>F</sub></th>
                    <th>IR</th>
                    <th>MP</th>
                    <th colspan="2">HRMS</th>
                    <th colspan="4">Specific Rotation</th>
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
                    <td>Chiral</td>
                    <td>[α]</td>
                    <td>c</td>
                    <td>Solvent</td>
                </tr>
            </thead>

            <tbody>

                @forelse ($compounds as $compound)
                    <tr>
                        <td><img src="/svg/{{ $compound->id }}.svg" height="70" style="margin-top:-10px;"></td>
                        <td><a href="/compounds/{{ $compound->id }}/edit">{{ $compound->label }}</a></td>
                        <td><input type="checkbox" {{ $compound->proton_nmr ? 'checked' : '' }} disabled></td>
                        <td><input type="checkbox" {{ $compound->carbon_nmr ? 'checked' : '' }} disabled></td>
                        <td>{{ $compound->retention }}</td>
                        <td><input type="text" disabled value="{{ $compound->infrared }}"></td>
                        <td>{{ $compound->melting_point }}</td>
                        <td>{{ $compound->mass_adduct }}</td>
                        <td>{{ $compound->mass_measured }}</td>
                        <td><input type="checkbox" checked disabled></td>
                        <td>{{ $compound->alpha_sign }} {{ $compound->alpha_value }}</td>
                        <td>{{ $compound->alpha_concentration }} </td>
                        <td>{{ $compound->alpha_solvent }}</td>
                        <td>{{ $compound->notes }}</td>
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
