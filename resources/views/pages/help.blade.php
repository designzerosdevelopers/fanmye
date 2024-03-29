@extends('layouts.user-no-nav')

@section('page_title', __($page->title))
@section('share_url', route('home'))
@section('share_title', getSetting('site.name') . ' - ' . getSetting('site.slogan'))
@section('share_description', getSetting('site.description'))
@section('share_type', 'article')
@section('share_img', GenericHelper::getOGMetaImage())

@section('content')
    <div class="container pt-4">
        <div class="page-content-wrapper pb-5">
            <div class="row">
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="page-header mt-2 mb-5 text-center">
                        <h1 class=" text-bold">{{$page->title}}</h1>
                        @if(in_array($page->slug,['help','privacy','terms-and-conditions']))
                        <p class="text-muted mb-0 mt-2">{{__("Last updated")}}: {{$page->updated_at->format('Y-m-d')}}</p>
                            @endif
                    </div>
                    {!! $page->content  !!}
                </div>
            </div>
        </div>
    </div>
@stop
