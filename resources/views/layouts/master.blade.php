<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- <title>{{ config('app.name', 'Laravel') }} @yield('title')</title> -->
    <title>
        @if(View::hasSection('title'))
            @yield('title')
        @else
            {{ config('app.name') }}
        @endif
    </title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">


    <style>
        #app {
            background-color: white;
        }
        .add {
            font-size: 30px;
            font-weight: 800;
        }

        .new-btn {
            background: white;
            display: inline-block;
            position: relative;
            border: 1px solid #8C8C8CFF;
            border-radius: 10px;
            float: left;
            padding: 35px;
            margin-top: 20px;
            max-width: 150px;
            max-height: 150px;
        }

        .compound {
            display: inline-block;
            background: white;
            border: 1px solid #494949FF;
            border-radius: 10px;
            margin: 5px;
            max-width: 150px;
            max-height: 150px;
            overflow: hidden;
            text-align: center;
        }

        span.label {
            display: block;
            text-align: center;
            color: black;
            font-family: arial;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .container-mx-auto {
            margin-right: 17px;
            margin-left: 17px;
        }
    </style>

    @yield('head')

</head>
<body>
    <div id="app">
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    @yield('scripts')
</body>
</html>
