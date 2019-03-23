@extends ('layouts.master')

@section ('content')

    @include('layouts.navbar')

    <div style="background-color:white;">
        @include('database._searchbar')

        <div 
            class="search-results" 
            style="margin-left: 2rem; margin-right: 2rem;" 
            name="search-results" 
            id="search-results"
            >
            <h1>Results ({{ $chemicals->count() }})</h1>
                <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>CAS</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chemicals as $chemical)
                            @include ('database.textsearch.match')
                        @endforeach
                    </tbody>
                </table>
                </div>
        </div>
    </div>
@endsection

@section ('scripts')
    <script type="text/javascript">
        function revealExamples() {
            $('#examples-wildcard').fadeIn();
            $('#show-examples').remove();
        }

        $(document).ready(function() {
            document.getElementById('search-results').scrollIntoView();
        });

        function clipboard(that) {
            var inp = document.createElement('input');
            document.body.appendChild(inp);
            inp.value = that.textContent;
            inp.select();
            document.execCommand("copy", false);
            inp.remove();

            alert("Copied CAS number to clipboard");
        }
    </script>
@endsection
