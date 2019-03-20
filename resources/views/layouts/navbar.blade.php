<nav class="navbar navbar-default navbar-static-top" style="z-index:1">
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
                            <li><a href="/">Compounds</a></li>
                            <li><a href="/reactions">Reactions</a></li>
                            <li><a href="/projects">Projects</a></li>
                            <li><a href="/compounds/new">Add new Compound</a></li>
                            <li><a href="/compounds/import">Import Compound</a></li>
                            @if (Auth::user()->students->count())
                            
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true">
                                    Users <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu">
                                        @forelse(Auth::user()->students as $student)
                                            <li>
                                                <a href="/users/{{ $student->id }}/impersonate"> {{ $student->name }}</a>
                                            </li>
                                        @empty
                                            <li></li>
                                        @endforelse

                                        @if (in_array(Auth::user()->email, config('app.admins')))
                                            <hr>
                                            &nbsp; Users:
                                            @forelse(\App\User::all() as $user)
                                                <li>
                                                    <a href="/users/{{ $user->id }}/impersonate"> {{ $user->name }}</a>
                                                </li>
                                            @empty
                                                <li></li>
                                            @endforelse
                                        @endif
                                </ul>
                            </li>

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
                                        <a href="/projects">Manage projects</a>
                                    </li>
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
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
