@extends ('layouts.master')

@section('head')
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/dojo/1.11.2/dojo/dojo.js"></script>
    <script type="text/javascript" src='/js/JSDraw/Scilligence.JSDraw2.Pro.js'></script>
    <style>
    #mobile-editor {
        width: 100%;
        height: 400px;
    }
    @media only screen and (max-width: 768px) {
        #mobile-editor {
            width: 320px;
            height: 300px;
            margin-left: 10px;
            margin-right: 10px;
        }
    }
    </style>
@endsection


@section ('content')

@include('layouts.navbar')

<center>
        <img src="/{{ $compound->SVGPath }}" width="120">
        
        <hr>
        
        <h1> {{ $compound->label }} </h1>

        <a href="/compounds/{{ $compound->id }}" class="btn btn-small btn-primary">View compound</a>
        <a href="/compounds/{{ $compound->id }}/delete" class="btn btn-small btn-danger">Delete compound</a>

        <h3 style="margin-bottom:25px;">Edit info</h3>

    </center>
        
        <div style="width:90%">
            <form class="form-horizontal" autocomplete="off" method="POST" action="/compounds/{{ $compound->id }}">
                {{ csrf_field() }}
                {{ method_field('put') }}
              <div class="form-group">
                <label for="label" class="col-sm-2 control-label">Label</label>
                <div class="col-sm-10">
                  <input type="text" class="form-control" name="label" id="label" value="{{ $compound->label }}" placeholder="JBN123" tabindex="1">
                </div>
              </div>

              <div class="form-group">
                <label for="Rf" class="col-sm-2 control-label">Rf</label>
                <div class="col-sm-10">
                  <input type="text" class="form-control" id="Rf" name="Rf" placeholder="0.30 (EtOAc / cHex = 1:1)" tabindex="2" value="{{ $compound->retention }}">
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label">NMR</label>
                <div class="col-sm-10">
                    <label class="checkbox-inline">
                      <input name="NMR[]" type="checkbox" id="H_NMR" value="H_NMR" tabindex="3" {{ $compound->proton_nmr ? 'checked' : '' }}> <sup>1</sup>H
                    </label>
                    <label class="checkbox-inline">
                      <input name="NMR[]" type="checkbox" id="C_NMR" value="C_NMR" tabindex="4" {{ $compound->carbon_nmr ? 'checked' : '' }}> <sup>13</sup>C
                    </label>
                </div>
              </div>

              <div class="form-group">
                <label for="IR" class="col-sm-2 control-label">IR</label>
                <div class="col-sm-10">
                  <textarea name="IR" id="IR" class="form-control" rows="1" placeholder="3500, 2200, 1750, 1300, 1100, 900" tabindex="5">{{ $compound->infrared }}</textarea>
                </div>
              </div>

                <show-melting-point data="{{ $compound->melting_point }}"></show-melting-point>

                <show-hrms-data 
                    mass_adduct="{{ $compound->mass_adduct }}" 
                    mass_calculated="{{ $compound->mass_calculated }}" 
                    mass_measured="{{ $compound->mass_measured }}"
                ></show-hrms-data>

              <show-rotation-data
                    alpha_sign="{{ $compound->alpha_sign }}"
                    alpha_value="{{ $compound->alpha_value }}"
                    alpha_concentration="{{ $compound->alpha_concentration }}"
                    alpha_solvent="{{ $compound->alpha_solvent }}"
              ></show-rotation-data>

              <div class="form-group">
                    <div class="col-sm-2 control-label">
                        <label>Structure</label>
                    </div>
                    <div class="col-sm-10">
                        <div class="JSDraw"
                         id="mobile-editor"
                         dataformat='molfile'
                         data="{{ $compound->molfile }}"
                         skin="w8"
                         ondatachange="molchange"
                        ></div>
                    </div>
                    <input type="hidden" name="molfile" value="{{ $compound->molfile }}" id="molfile">
                    <input type="hidden" name="molweight" id="molweight">
                    <input type="hidden" name="formula" id="formula">
                    <input type="hidden" name="exact_mass" id="exact_mass">
                    <input type="hidden" name="user_updated_molfile" id="user_updated_molfile">
                </div>

                <div class="form-group">
                  <label for="notes" class="col-sm-2 control-label">Notes</label>
                  <div class="col-sm-10">
                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Anything to say on (the synthesis of) this compound?">{{ $compound->notes }}</textarea>
                  </div>
                </div>

              <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                  <button type="submit" class="btn btn-lg btn-block btn-success">Save changes</button>
                </div>
              </div>
            </form>
            <br><br>
        </div>

@endsection

@section('scripts')
<script type="text/javascript">

    dojo.addOnLoad(function() {
        var jsd2 = new JSDraw("large-editor");
        new JSDraw("mobile-editor").setHtml(jsd2.getHtml());
    });

    function molchange(jsdraw) {
        document.getElementById("molfile").value = JSDraw.get("mobile-editor").getMolfile();
        document.getElementById("molweight").value = JSDraw.get("mobile-editor").getMolWeight();
        document.getElementById("formula").value = JSDraw.get("mobile-editor").getFormula();
        document.getElementById("exact_mass").value = JSDraw.get("mobile-editor").getExactMass();
        document.getElementById("user_updated_molfile").value = 'true';
    }
</script>
@endsection
