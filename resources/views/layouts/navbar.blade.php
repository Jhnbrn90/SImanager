<nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse" aria-expanded="false">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="{{ url('/') }}">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <!-- Left Side Of Navbar -->
                    <ul class="nav navbar-nav">
                        @auth
                            &nbsp;
                            <li><a href="/">Overview</a></li>
                            <li><a href="/compounds/new">Add new Compound</a></li>
                            <li><a href="/compounds/import">Import from document</a></li>
                            @if (Auth::user()->students->count())
                                <li><a href="/students">View students</a></li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @guest
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a href="{{ route('register') }}">Register</a></li>
                        @else
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true">
                                    Hello, {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="/supervisor/add">Add supervisor</a>
                                    </li>

                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                        @if (Auth::user()->students->count())
                                            <hr>
                                            &nbsp; Students:
                                        @endif
                                        @forelse(Auth::user()->students as $student)
                                            <li>
                                                <a href="/students/view/data/{{ $student->id }}"> {{ $student->name }}</a>
                                            </li>
                                        @empty
                                            <li></li>
                                        @endforelse

                                        @if (in_array(Auth::user()->email, config('app.admins')))
                                            <hr>
                                            &nbsp; Users:
                                            @forelse(\App\User::all() as $user)
                                                <li>
                                                    <a href="/students/view/data/{{ $user->id }}"> {{ $user->name }}</a>
                                                </li>
                                            @empty
                                                <li></li>
                                            @endforelse
                                        @endif
                                
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
