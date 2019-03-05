@extends ('layouts.master')

@section('head')
    <style>
    #mobile-editor {
        width: 100%;
        height: 400px;
    }
    @media only screen and (max-width: 768px) {
        #mobile-editor {
            width: 120px;
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
        <h1>Add new Reaction</h1>
    </div>

    <hr>

    <div class="text-center">
      @forelse ($reaction->startingMaterials as $compound)
        @if (! $loop->first) 
          + <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
        @else
         <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
        @endif
      @empty
        Add starting materials / reagents.
      @endforelse

      <i class="fa fa-arrow-right" style="font-size: 50px;"></i>

      @forelse ($reaction->products as $compound)
        
        @if (! $loop->first) 
          + <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
        @else
         <a href="/compounds/{{ $compound->id }}"><img src="/{{ $compound->SVGPath }}"></a>
        @endif

      @empty
        Add products.
      @endforelse

    </div>
  <hr>
  <div>
  <h1>Add a component</h1>

  <form action="/reactions/{{ $reaction->id }}" method="POST">
    {{ @csrf_field() }}
    {{ @method_field('PATCH') }}

    <input type="hidden" name="project" value="{{ $reaction->project->id }}">
    
    <div style="margin-bottom:10px;">
      <label class="radio-inline">
        <input type="radio" name="type" id="starting_material" value="starting_material"> Starting material
      </label>
      <label class="radio-inline">
        <input type="radio" name="type" id="reagent" value="reagent"> Reagent
      </label>
      <label class="radio-inline">
        <input type="radio" name="type" id="product" value="product"> Product
      </label>
      <button style="margin-left: 10px;" class="btn btn-success" type="submit">Add to reaction</button>
    </div>

    <div class="JSDraw"
         id="mobile-editor"
         dataformat='molfile'
         data=""
         skin="w8"
         ondatachange="molchange"
     ></div>

      <input type="hidden" name="molfile" id="molfile">
      <input type="hidden" name="molweight" id="molweight">
      <input type="hidden" name="formula" id="formula">
      <input type="hidden" name="exact_mass" id="exact_mass">

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

