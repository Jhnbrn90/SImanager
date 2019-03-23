<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="manifest" href="/icons/site.webmanifest">
    <link rel="mask-icon" href="/icons/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="/icons/favicon.ico">
    <meta name="msapplication-TileColor" content="#2b5797">
    <meta name="msapplication-config" content="/icons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

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
        .sticky {
            z-index: 999;
            position: -webkit-sticky;
            position: sticky;
            top: 0px;
            border-top: 2px dotted black;
            border-bottom: 2px dotted black;
        }

    </style>

    @yield('head')

</head>
<body>
    <div id="app">
        
        @if (session()->has('impersonate'))
            <div class="text-center bg-grey text-grey-darkest sticky" style="padding: 2rem 2rem; width:100%;">
                You are now acting on behalf of <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}). 
                <br>
                <a class="font-medium text-blue-darker hover:text-blue-dark" href="/users/stop">Switch to your own account</a>
            </div>
        @endif

        @yield('content')

    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    
    @yield('scripts')

</body>
</html>
