<!DOCTYPE html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ $favicon }}">
    <style>
        /* Set the size of the div element that contains the map */
        #map {
            height: 500px;
            /* The height is 400 pixels */
            width: 100%;
            /* The width is the width of the web page */
        }

        .flash-container1 {
            position: absolute;
            right: 16px !important;
            /* left: 1616px; */
            top: 16px !important;
            z-index: 1001 !important;
            /* padding-left: 57px; */
        }
    </style>

</head>
<div class="flash-container1">
    @if(Session::has('message'))
    <div class="alert text-center {{ Session::get('alert-class') }}" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        {{ Session::get('message') }}
    </div>
    @endif
</div>
<html lang="en-IN" xmlns:fb="http://ogp.me/ns/fb#">

<head>
    <title>{{ $title ?? Helpers::meta((!isset($exception)) ? Route::current()->uri() : '', 'title') }}
        {{ $additional_title ?? '' }}</title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">


    <meta name="description"
        content="{{ Helpers::meta((!isset($exception)) ? Route::current()->uri() : '', 'description') }}">
    <meta name="keywords"
        content="{{ Helpers::meta((!isset($exception)) ? Route::current()->uri() : '', 'keywords') }}">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">


    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700" rel="stylesheet">
    {!! Html::style('admin_assets/dist/css/jquery.datetimepicker.min.css') !!}
    {!! Html::style('css/bootstrap.min.css') !!}
    {!! Html::style('css/bootstrap.css') !!}
    {!! Html::style('css/font-awesome.min.css') !!}
    {!! Html::style('css/main.css?v='.$version) !!}
    {!! Html::style('css/common.css?v='.$version) !!}
    {!! Html::style('css/common1.css?v='.$version) !!}
    {!! Html::style('css/styles.css?v='.$version) !!}
    {!! Html::style('css/jquery.bxslider.css') !!}
    {!! Html::style('css/jquery.sliderTabs.min.css') !!}
    {!! Html::style('css/jquery-ui/jquery-ui.min.css') !!}

    <link rel="stylesheet" type="text/css"
        href=" https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-149445554-4"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-149445554-4');
    </script>
    {!! Html::script('js/jquery-1.11.3.js') !!}
    {!! Html::script('js/jquery-ui.min.js') !!}
</head>

<body ng-app="App">