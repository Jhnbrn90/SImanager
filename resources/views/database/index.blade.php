@extends ('layouts.master')

@section ('content')
    @include ('layouts.navbar')

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
