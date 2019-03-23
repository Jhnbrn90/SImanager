@extends('layouts.master')

@section ('head')
    <style>
    #mobile-editor {
        width: 650px;
        height: 350px;
    }
    @media only screen and (max-width: 768px) {
        #mobile-editor {
            width: 300px;
            height: 300px;
            margin-left: 10px;
            margin-right: 10px;
        }
    }
    </style>

    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/dojo/1.11.2/dojo/dojo.js"></script>
    <script type="text/javascript" src='/js/JSDraw/Scilligence.JSDraw2.Pro.js'></script>
@endsection

@section('content')
    @include ('layouts.navbar')
    
       <div class="container">
        <center>
            <div class="card" style="margin-top:-20px;">
                <div class="card-body">
                    <form method="POST" action="/database/substructure/search">
                        {{ csrf_field() }}
                    <h2>Substructure search</h2>

                    &bull; <a href="/database"> Back to search by name/cas </a>

                    <div class="form-check" style="margin-top: 12px; margin-bottom: 5px;">
                      <label class="form-check-label">
                        <input class="form-check-input" type="checkbox" name="exact" value="checked" {{ $exact or '' }}>
                        Exact search
                      </label>
                    </div>

                    <div class="form-group" style="padding-top:1rem;">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>

                    <div class="wrapper" style="width: 1px; height: 1px; overflow:hidden;">
                        <div class='JSDraw' id="large-editor" skin="w8" style="width:750px; height:350px"
                            dataformat='molfile' data="{{ $molfile or '' }}"></div>
                    </div>


                    <div class="JSDraw" id="mobile-editor" skin="w8" ondatachange='molchange'></div>


                    <script type="text/javascript">
                        dojo.addOnLoad(function() {
                            var jsd2 = new JSDraw("large-editor");
                            new JSDraw("mobile-editor").setHtml(jsd2.getHtml());
                        });

                        function molchange(jsdraw) {
                            document.getElementById("molfile").value = JSDraw.get("mobile-editor").getMolfile();
                        }
                    </script>

                    <input type="hidden" name="molfile" id="molfile" value="{{ $molfile or '' }}">

                    <br>
                    </form>
                </div>
            </div>
        </center>
    </div>

@endsection
