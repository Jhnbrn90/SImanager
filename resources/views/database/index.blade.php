@extends ('layouts.master')

@section ('content')
    @include ('layouts.navbar')

    <h1 class="text-center mb-8">Chemicals database</h1>
    
    @include('database.partials._message')

    @include ('database._searchbar')
@endsection

@section ('scripts')
    <script type="text/javascript">
        function revealExamples() {
            $('#examples-wildcard').fadeIn();
            $('#show-examples').remove();
        }
    </script>
@endsection
