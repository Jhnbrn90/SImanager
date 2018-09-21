@extends ('layouts.master')

@section('head')
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

    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/dojo/1.11.2/dojo/dojo.js"></script>
    <script type="text/javascript" src='/js/JSDraw/Scilligence.JSDraw2.Pro.js'></script>
@endsection

@section ('content')

@include ('layouts.navbar')

<div class="container">
    <div class="title">
        <h1> Add new compound </h1>
    </div>

    <hr>

<form class="form-horizontal" autocomplete="off" method="POST" action="/compounds">
    {{ csrf_field() }}
  <div class="form-group">
    <label for="label" class="col-sm-2 control-label">Label</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="label" id="label" placeholder="JBN123" tabindex="1">
    </div>
  </div>

  <div class="form-group">
    <label for="Rf" class="col-sm-2 control-label">Rf</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="Rf" name="Rf" placeholder="0.30 (EtOAc / cHex = 1:1)" tabindex="2">
    </div>
  </div>

  <div class="form-group">
    <label class="col-sm-2 control-label">NMR</label>
    <div class="col-sm-10">
        <label class="checkbox-inline">
          <input name="NMR[]" type="checkbox" id="H_NMR" value="H_NMR" tabindex="3"> <sup>1</sup>H
        </label>
        <label class="checkbox-inline">
          <input name="NMR[]" type="checkbox" id="C_NMR" value="C_NMR" tabindex="4"> <sup>13</sup>C
        </label>
    </div>
  </div>

  <div class="form-group">
    <label for="IR" class="col-sm-2 control-label">IR</label>
    <div class="col-sm-10">
      <textarea name="IR" id="IR" class="form-control" rows="1" placeholder="3500, 2200, 1750, 1300, 1100, 900" tabindex="5"></textarea>
    </div>
  </div>

  <div class="form-group">
    <label for="MP" class="col-sm-2 control-label">Melting Point (&deg; C.)</label>
    <div class="col-sm-10">
        <input type="text" class="form-control" id="MP" name="MP" placeholder="102 - 108" tabindex="6">
    </div>
  </div>

  <div class="form-group">
    <label for="mass_ion" class="col-sm-2 control-label">Mass Ion</label>
    <div class="col-sm-10">
        <select name="mass_ion" id="mass_ion" class="form-control">
          <option value="H+">H+</option>
          <option value="Na+">Na+</option>
          <option value="H-">Negative mode (H-)</option>
        </select>
    </div>
  </div>

  <div class="form-group">
    <label for="mass_found" class="col-sm-2 control-label">Found mass</label>
    <div class="col-sm-10">
        <input type="text" class="form-control" id="mass_found" name="mass_found" placeholder="221.0291">
    </div>
  </div>

  <div class="form-group">
    <label for="mass_calculated" class="col-sm-2 control-label">Calculated mass</label>
    <div class="col-sm-10">
        <input type="text" class="form-control" id="mass_calculated" name="mass_calculated" placeholder="221.0290">
    </div>
  </div>

  <div class="form-group">
    <label for="rotation_sign" class="col-sm-2 control-label">Specific Rotation</label>
    <div class="col-sm-1">
        <select name="rotation_sign" id="rotation_sign" class="form-control">
          <option value="+">&plus;</option>
          <option value="-">&minus;</option>
        </select>
    </div>
    
    <div class="col-sm-2">
        <div class="input-group">
            <div class="input-group-addon">[&alpha;]<sup>20</sup><sub>D</sub> = </div>
            <input type="text" class="form-control" name="rotation_value" placeholder="19.65">
        </div>
    </div>

    <div class="col-sm-2">
        <div class="input-group">
            <div class="input-group-addon">c = </div>
            <input type="text" class="form-control" name="rotation_concentration" placeholder="1.05">
        </div>
    </div>

    <div class="col-sm-3">
        <div class="input-group">
            <div class="input-group-addon">solvent</div>
            <input type="text" class="form-control" id="rotation_solvent" name="rotation_solvent" placeholder="CHCl3">
        </div>
    </div>
  </div>

  <div class="form-group">
        <div class="col-sm-2 control-label">
            <label>Structure</label>
        </div>
        <div class="col-sm-10">
            <div class="JSDraw"
             id="mobile-editor"
             dataformat='molfile'
             data=""
             skin="w8"
             ondatachange="molchange"
            ></div>
        </div>
        <input type="hidden" name="molfile" id="molfile">
    </div>

    <div class="form-group">
      <label for="notes" class="col-sm-2 control-label">Notes</label>
      <div class="col-sm-10">
        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Anything to say on (the synthesis of) this compound?"></textarea>
      </div>
    </div>

  <div class="form-group">
    <div class="col-sm-offset-2 col-sm-10">
      <button type="submit" class="btn btn-lg btn-block btn-primary">&plus; Add compound</button>
    </div>
  </div>
</form> 

<br><br>
<br><br>
<br><br>

</div>

<script type="text/javascript">
    dojo.addOnLoad(function() {
        var jsd2 = new JSDraw("large-editor");
        new JSDraw("mobile-editor").setHtml(jsd2.getHtml());
    });

    function molchange(jsdraw) {
        document.getElementById("molfile").value = JSDraw.get("mobile-editor").getMolfile();
    }
</script>


@endsection
