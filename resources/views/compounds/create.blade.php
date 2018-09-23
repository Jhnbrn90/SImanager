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
      <label for="label" class="col-sm-2 control-label">User</label>
      <div class="col-sm-10">
        <select name="user_id" class="form-control">
              <option value="{{ Auth::user()->id }}"> {{ Auth::user()->name }} (you)</option>
            @foreach (Auth::user()->students as $student)
                <option value="{{ $student->id }}"> {{ $student->name }} ({{ $student->email }})</option>
            @endforeach
        </select>
      </div>
    </div>

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

  <melting-point></melting-point>

  <hrms-data></hrms-data>

  <rotation-data></rotation-data>

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
        <input type="hidden" name="molweight" id="molweight">
        <input type="hidden" name="formula" id="formula">
        <input type="hidden" name="exact_mass" id="exact_mass">
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
    }
</script>
@endsection

