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

<form class="form-horizontal" autocomplete="off" method="POST" action="/compounds/import">
    {{ csrf_field() }}

    <div class="form-group">
      <label for="experimental" class="col-sm-2 control-label">Experimental</label>
      <div class="col-sm-10">
        <textarea name="experimental" id="experimental" class="form-control" rows="7" placeholder="RF = 0.2 (EtOAc/cHex = 2:3). 1H NMR (300 MHz, CDCl3) δ 5.38 (d, J = 8.5, 1.5 Hz, 1H), 4.82 (q, J = 8.3, 6.0 Hz, 1H), 4.05 (dd, J = 8.0, 5.9 Hz, 1H), 3.51 (t, J = 8.1 Hz, 1H), 3.17 (s, 2H), 2.85 – 2.68 (m, 1H), 1.75 (s, 3H), 1.61 (s, 11H), 1.06 (d, J = 6.2 Hz, 6H). IR (neat): νmax (cm-1): 2931, 2862, 1672, 1448, 1363, 1332, 1278, 1249, 1230, 1141, 1163, 1099, 1068, 1039, 1020. HRMS (ESI): m/z calculated for C15H28NO2+ [M+H]+ 254.2108, found: 254.2124. [α]D20    = + 7.16 (c = 1.68, CHCl3)."></textarea>
      </div>
    </div>

     <div class="form-group">
       <label class="col-sm-2 control-label">Project</label>
       <div class="col-sm-10">
         <select name="project" class="form-control">
             @foreach (Auth::user()->projects as $project)
                 <option value="{{ $project->id }}"> {{ $project->name }}</option>
             @endforeach
             @forelse (Auth::user()->students as $student)
                 @foreach($student->projects as $project)
                   <option value="{{ $project->id }}"> {{ $project->name }} ({{ $student->email }})</option>
                 @endforeach
             @empty
             @endforelse
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

