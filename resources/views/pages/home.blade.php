@extends('layouts.generic')

@section('page_description', getSetting('site.slogan'))
@section('share_url', route('home'))
@section('share_title', getSetting('site.name') . ' - ' . getSetting('site.slogan'))
@section('share_description', getSetting('site.description'))
@section('share_type', 'article')
@section('share_img', GenericHelper::getOGMetaImage())

@section('scripts')
    <script type="application/ld+json">
  {
    "@context": "http://schema.org",
    "@type": "Organization",
    "name": "{{getSetting('site.name')}}",
    "url": "{{getSetting('site.app_url')}}",
    "address": ""
  }
</script>
@stop

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/pages/home.css',
            '/css/pages/search.css',
         ])->withFullUrl()
    !!}
@stop

@section('content')
    <div class="home-header min-vh-75 relative pt-2" >
        <div class="container h-100">
            <div class="row d-flex flex-row align-items-center h-100">
                <div class="col-12 col-md-6 mt-4 mt-md-0">
                    <h1 class="font-weight-bold text-gradient bg-gradient-primary">{{__('Make more money')}}</h1>
                    <h1 class="font-weight-bold text-gradient bg-gradient-primary">{{__('with your content')}}.</h1>
                    <p class="font-weight-bold mt-3">🚀 {{__("Start your own premium creators platform with our ready to go solution.")}}</p>
                    <div class="mt-4">
                        <a href="{{route('login')}}" class="btn btn-grow bg-gradient-primary  btn-round mb-0 me-1 mt-2 mt-md-0 ">{{__('Try for free')}}</a>
                        <a href="{{route('search.get')}}" class="btn btn-grow btn-link  btn-round mb-0 me-1 mt-2 mt-md-0 ">
                            @include('elements.icon',['icon'=>'search-outline','centered'=>false])
                            {{__('Explore')}}</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-none d-md-block p-5">
                    <img src="{{asset('/img/home-header.svg')}}" alt="{{__('Make more money')}}"/>
                </div>
            </div>
        </div>
    </div>

    <div class="my-5 py-5 home-bg-section">
        <div class="container my-5">
            <div class="row">
                <div class="col-12 col-md-4 mb-5 mb-md-0">
                    <div class="d-flex justify-content-center">
                        <img src="{{asset('/img/home-scene-1.svg')}}" class="img-fluid home-box-img" alt="{{__('Premium & Private content')}}">
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        <div class="col-12 col-md-10 text-center">
                            <h5 class="text-bold">{{__('Premium & Private content')}}</h5>
                            <span>{{__('Enjoy high quality content, made for you and the ones like you.')}} </span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-5 mb-md-0">
                    <div class="d-flex justify-content-center">
                        <img src="{{asset('/img/home-scene-2.svg')}}" class="img-fluid home-box-img" alt="{{__('Private chat & Tips')}}">
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        <div class="col-12 col-md-10 text-center">
                            <h5 class="text-bold">{{__('Private chat & Tips')}}</h5>
                            <span>{{__('Enjoy private conversations and get tipped for your content.')}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-5 mb-md-0">
                    <div class="d-flex justify-content-center">
                        <img src="{{asset('/img/home-scene-3.svg')}}" class="img-fluid home-box-img" alt="{{__('Secured assets & Privacy focus')}}">
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        <div class="col-12 col-md-10 text-center">
                            <h5 class="text-bold">{{__('Secured assets & Privacy focus')}}</h5>
                            <span>{{__("Your content gets safely uploaded to the cloud and you have full control over your account.")}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="mt-5 pb-3 pt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 d-none d-md-flex justify-content-center">
                    <img src="{{asset('/img/home-creators.svg')}}" class="home-mid-img" alt="{{__('Make more money')}}">
                </div>
                <div class="col-12 col-md-6">
                    <div class="w-100 h-100 d-flex justify-content-center align-items-center">
                        <div class="pl-4 pl-md-5">
                            <h2 class="font-weight-bold m-0">{{__('Make more money')}}</h2>
                            <h2 class="font-weight-bold m-0">{{__('with your content')}}.</h2>
                            <div class="my-4 col-9 px-0">
                                <p>Become a creator and post your premium content securely on our platform. Make more money with your content.</p>
                            </div>
                            <div>
                                <a href="{{Auth::check() ? route('my.settings',['type'=>'verify']) : route('login') }}" class="btn bg-gradient-primary btn-grow btn-round mb-0 me-1 mt-2 mt-md-0 p-3">{{__('Become a creator')}}</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="mt-5 pb-3 pt-5 home-bg-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="font-weight-bold">{{__('Main Features')}}</h2>
                <p>Here&#39;s a glimpse at the main features on Fanmye</p>
            </div>
            <div class="row">
                <div class="col-12 col-md-4 mb-5 btn-grow px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row-reverse">
                        @include('elements.icon',['icon'=>'phone-portrait-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("Mobile Ready")}}</h5>
                    <p class="mb-0">{{__("Cross compatible & mobile first design.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row-reverse">
                        @include('elements.icon',['icon'=>'cog-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("Advanced Settings")}}</h5>
                    <p class="mb-0">{{__("Easy to use, fully featured creator settings.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row-reverse">
                        @include('elements.icon',['icon'=>'people-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("User Subscriptions")}}</h5>
                    <p class="mb-0">{{__("Easy to use and reliable subscriptions system.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row-reverse">
                        @include('elements.icon',['icon'=>'list-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("User Feed & Locked Posts")}}</h5>
                    <p class="mb-0">{{__("Advanced feed system, pay to unlock posts.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow text-left px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row">
                        @include('elements.icon',['icon'=>'moon-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("Light & Dark Themes")}}</h5>
                    <p class="mb-0">{{__("Eazy to customize themes, dark & light mode.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow text-left px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row">
                        @include('elements.icon',['icon'=>'language-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("RTL & Locales")}}</h5>
                    <p class="mb-0">{{__("Fully localize your site with languages & RTL.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow text-left px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row">
                        @include('elements.icon',['icon'=>'chatbubbles-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("Live Chat & Notifications")}}</h5>
                    <p class="mb-0">{{__("Live user messenger & User notifications.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow text-left px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row">
                        @include('elements.icon',['icon'=>'bookmarks-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("Post Bookmarks & User lists")}}</h5>
                    <p class="mb-0">{{__("Stay updated with list users and bookmarks.")}}</p>
                </div>

                <div class="col-12 col-md-4 mb-5 btn-grow text-left px-4 py-3 rounded my-2 w-100">
                    <div class="flex-row">
                        @include('elements.icon',['icon'=>'flag-outline','variant'=>'large','centered'=>false,'classes'=>''])
                    </div>
                    <h5 class="text-bold">{{__("Content Flagging And User reports")}}</h5>
                    <p class="mb-0">{{__("Stay safe with user and content reporting.")}}</p>
                </div>

            </div>
        </div>
    </div>

    <div class="my-5 py-2">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="font-weight-bold">{{__("Technologies Used")}}</h2>
                <p>{{__("Built on secure, scalable and reliable techs")}}</p>
            </div>
            <div class="d-flex align-items-center justify-content-center">
                <div class="d-flex justify-content-center align-items-center row col">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Discover_Card_logo.svg/2560px-Discover_Card_logo.svg.png" class="mx-3 mb-2 grayscale coinbasae-logo" title="{{ucfirst(__("coinbase"))}}" alt="{{__("coinbase")}}"/>
                    <img src="https://www.pngitem.com/pimgs/m/160-1603803_american-express-logotype-stacked-american-express-logo-png.png" class="mx-3 mb-2 grayscale coinbasae-logo" title="{{ucfirst(__("coinbase"))}}" alt="{{__("coinbase")}}"/>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/MasterCard_early_1990s_logo.svg/200px-MasterCard_early_1990s_logo.svg.png" class="mx-3 mb-2 grayscale coinbasae-logo" title="{{ucfirst(__("coinbase"))}}" alt="{{__("coinbase")}}"/>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png" class="mx-3 mb-2 grayscale coinbasae-logo" title="{{ucfirst(__("coinbase"))}}" alt="{{__("coinbase")}}"/>
                    
                </div>
            </div>
        </div>
    </div>

    <div class="my-5 py-5 home-bg-section">
        <div class="container">
           {{-- <div class="text-center mb-4">
                <h2 class="font-weight-bold">{{__("Featured creators")}}</h2>
                <p>{{__("Here's list of currated content creators to start exploring now!")}}</p>
            </div>--}}

            <div class="creators-wrapper">
                <div class="row px-3">
                    @if(count($featuredMembers))
                        @foreach($featuredMembers as $member)
                            <div class="col-12 col-md-4 p-1">
                                <div class="p-2">
                                    @include('elements.vertical-member-card',['profile' => $member])
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="py-4 my-4 white-section ">
        <div class="container">
            <div class="text-center">
                <h3 class="font-weight-bold">{{__("Got questions?")}}</h3>
                <p>{{__("Don't hesitate to send us a message at")}} - <a href="{{route('contact')}}">{{__("Contact")}}</a> </p>
            </div>
        </div>
    </div>
@stop
