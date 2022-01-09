@extends('template_merchant_site')
@section('main')

@php
    $accord = 0;
@endphp

<style>
    .bg-accent {
        background-color: hsl(0, 0%, 98%);
        min-height: 80vh;
    }
</style>

<div class="content-wrapper bg-accent">
    <div class="container-fluid">
        <h3 class="tracking-header">{{$merchant->name}}</h3>
        <h3 class="tracking-header">{{$date}}</h3>
        <div class="row">
            @foreach ($orders as $key => $value)
                @if ($key == 'assigned' || $key == 'picked_up')
                <div class="col-lg-3 col-md-6">
                @else
                <div class="col-lg-3 col-md-12">
                @endif
                @if ($key == 'assigned' || $key == 'picked_up')
                    <div class="panel panel-danger" style="border-radius: 4px;">
                @elseif ($key == 'new')
                    <div class="panel panel-primary" style="border-radius: 4px;">
                @else
                    <div class="panel panel-success" style="border-radius: 4px;">
                @endif
                        <div class="panel-heading">
                        @if ($key == 'new')
                            <h3 class="panel-title"><strong>Searching for driver</strong></h3>
                        @elseif ($key == 'assigned')
                            <h3 class="panel-title"><strong>En route to pick up</strong></h3>
                        @elseif ($key == 'picked_up')
                            <h3 class="panel-title"><strong>En route to drop off</strong></h3>
                        @elseif ($key == 'delivered')
                            <h3 class="panel-title"><strong>Delivered</strong></h3>
                        @endif
                        </div>
                        <div class="panel-body">
                            @foreach($value as $order)
                                    @php
                                        $accord += 1;
                                    @endphp
                                    <div class="row content box box-solid order-box" style="padding:0;">
                                        <div class="col-lg-12 top-bottom-inner-buffer">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <h4><span class="pull-left">Delivery #{{$order->id}}</span></h4>
                                                    <a href="#colps-{{$accord}}" data-toggle="collapse" aria-expanded="true" aria-controls="colps-{{$accord}}" class="pull-right">&nbsp;<i class="fa fa-chevron-circle-up fa-2x" aria-hidden="true"></i></a>
                                                    @if ($key != 'new' && $key != 'delivered')
                                                    <h4><span class="label label-warning pull-right">ETA: <strong>{{$order->eta ? $order->eta : 0 }} minutes</strong></span></h4>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="collapse in" id="colps-{{$accord}}" aria-expanded="true">
                                            <div class="row top-bottom-buffer higlight-element top-bottom-inner-buffer">
                                                <div class="col-lg-9 col-xs-9top-bottom-buffer">
                                                    <small class="text-muted">Got at <span class="time-to-convert">{{$order->created_at}}</span></small><br>
                                                    <small class="text-muted">Deliver up to <span class="time-to-convert">{{$order->delivery_time}}</span></small>
                                                </div>
                                            </div>
                                            <div class="row top-bottom-buffer higlight-element top-bottom-inner-buffer">
                                                <div class="col-lg-2 col-xs-1 top-bottom-buffer">
                                                    <i class="fa fa-user-o fa-2x text-success" aria-hidden="true"></i>
                                                </div>
                                                <div class="col-lg-10 col-xs-11">
                                                    <div class="row">
                                                        <div class="col-lg-12 top-bottom-buffer">
                                                            <span class="align-middle text-muted">Customer: </span>
                                                            <span class="align-middle">{{ $order->customer_name }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12 top-bottom-buffer">
                                                            <i class="fa fa-map-marker pull-left" aria-hidden="true"></i>
                                                            <span class="align-middle">{{$order->drop_location}}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($key != 'new')
                                            <div class="row top-bottom-buffer higlight-element top-bottom-inner-buffer">
                                                <div class="col-lg-2 col-xs-1 top-bottom-buffer">
                                                    <i class="fa fa-user-circle-o text-info fa-2x" aria-hidden="true"></i>
                                                    {{-- <img style="width: 30px; height: 30px; border-radius: 50%; margin: 2px;" src="https://rideon-cdn.sgp1.digitaloceanspaces.com/images/users/55516004/profile_picture_55516004.jpeg" alt="user-img"> --}}
                                                </div>
                                                <div class="col-lg-10 col-xs-11">
                                                    <div class="row">
                                                        <div class="col-lg-12 top-bottom-buffer">
                                                            <span class="align-middle text-muted">Driver: </span>
                                                            <span class="align-middle">{{ $order->first_name }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12 top-bottom-buffer">
                                                            <i class="fa fa-phone pull-left" aria-hidden="true"></i>
                                                            <span class="align-middle">{{$order->driver_phone}}</span>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12 top-bottom-buffer">
                                                            {{-- <h4 style="margin:0;"><span class="label label-warning">ETA: <strong>{{$order->eta ? $order->eta : 0 }} minutes</strong></span></h4> --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            </div>
                                        </div>
                                    </div>
                            @endforeach
                            @if ($value->count() == 0)
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h3 class="panel-title"><strong>No orders found</strong></h3>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    {{-- <input type="hidden" id="app-default-timezone" name="default_timezone" value="{{ date_default_timezone_get() }}"> --}}
</div>
<script>
    // Init freshchat
    function initFreshChat() {
        window.fcWidget.init({
            token: "e749c074-1b71-45cd-bfc8-4ae1d8bd2b84",
            host: "https://wchat.freshchat.com"
        });
    }
    function initialize(i,t){var e;i.getElementById(t)?initFreshChat():((e=i.createElement("script")).id=t,e.async=!0,e.src="https://wchat.freshchat.com/js/widget.js",e.onload=initFreshChat,i.head.appendChild(e))}function initiateCall(){initialize(document,"freshchat-js-sdk")}window.addEventListener?window.addEventListener("load",initiateCall,!1):window.attachEvent("load",initiateCall,!1);

    // freschat initialization finished
    var interval_id;

    function reload_page(){
        location.reload(true);
    }

    $(window).focus(function() {
    if (!interval_id)
        interval_id = setInterval(reload_page, 60000);
    });

    $(window).blur(function() {
        clearInterval(interval_id);
        interval_id = 0;
    });

    window.onload = function timeConvert(){
        let dates =  document.getElementsByClassName("time-to-convert");
        for (var i = 0; i < dates.length; i++){
            let qq = moment.tz(dates.item(i).innerHTML, "{{ date_default_timezone_get() }}");
            dates.item(i).innerHTML = qq.local().format('lll');
        }
    }
</script>
@endsection