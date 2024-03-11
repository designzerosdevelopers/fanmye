
<!doctype html>
<html class="h-100" dir="{{GenericHelper::getSiteDirection()}}" lang="{{session('locale')}}">
<head>
    @include('template.head')
</head>
<body class="d-flex flex-column">
    <nav class="navbar navbar-expand-md {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white') : (Cookie::get('app_theme') == 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'))}} shadow-sm ">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="{{asset( (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo')) : (Cookie::get('app_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo'))) )}}" class="d-inline-block align-top mr-1 ml-3" alt="{{__("Site logo")}}">
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}" >
                <span class="navbar-toggler-icon"></span>
            </button>
    
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                            </li>
                        @endif
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

<div class="flex-fill">
   
    <div class="table-wrapper mx-5">
        <div>
            <div class="col py-3 text-bold border-bottom">
                <div class="col-lg-12 text-truncate d-md-block text-center">{{__('Your shorten link information')}}</div>
            </div>

            <!-- Table Header -->
            <div class="row">
                <div class="col"><b>Title</b></div>
                <div class="col"><b>Visitors</b></div>
                <div class="col"><b>Sign Ups</b></div>
                <div class="col"><b>Subscribers</b></div>
            </div>

            <!-- Table Body -->
                    <div class="row">
                        <div class="col">{{$link->title}}</div>
                        <div class="col">{{$link->visitor}}</div>
                        <div class="col">{{$link->sign_up}}</div>
                        <div class="col">{{$link->subscriber}}</div>
                    </div>
        </div>
    </div>
</div>

@include('template.footer')
@include('template.jsVars')
@include('template.jsAssets')
</body>
</html>