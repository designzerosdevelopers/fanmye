<!doctype html>
<html class="h-100" dir="{{GenericHelper::getSiteDirection()}}" lang="{{session('locale')}}">
<head>
    
    @include('template.head',['additionalCss' => [
                '/libs/animate.css/animate.css',
                '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css',
                '/css/side-menu.css',
             ]])
             <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
             <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
             <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
             <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
</head>
<body class="d-flex flex-column">

<div class="flex-fill">
    @include('template.user-side-menu')

    <div class="container-xl" style="scroll-behavior: smooth;">
    <!--<div class="container-xl overflow-x-hidden-m">-->

        <div class="row main-wrapper">
            <div class="col-2 col-md-3 pt-4 p-0 d-none d-md-block">
                @include('template.side-menu')
            </div>
            <div class="col-12 col-md-9 min-vh-100 border-left px-0 overflow-x-hidden-m content-wrapper {{(in_array(Route::currentRouteName(),['feed','profile','my.messenger.get','search.get','my.notifications','my.bookmarks','my.lists.all','my.lists.show','my.settings','posts.get']) ? '' : 'border-right' )}}">
                @yield('content')
            </div>
        </div>
        <div class="d-block d-md-none fixed-bottom">
            @include('elements.mobile-navbar')
        </div>
    </div>

</div>
@if(getSetting('compliance.enable_age_verification_dialog'))
    @include('elements.site-entry-approval-box')
@endif
@include('template.footer-compact',['compact'=>true])
@include('template.jsVars')
@include('template.jsAssets',['additionalJs' => [
               '/libs/jquery-backstretch/jquery.backstretch.min.js',
               '/libs/wow.js/dist/wow.min.js',
               '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js',
               '/js/SideMenu.js'
]])
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
</body>
</html>
