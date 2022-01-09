@extends('admin.template')
@section('main')

@php
    $accord = 0;
@endphp

<div class="content-wrapper">
    <section class="content-header">
        <h4>Delivery Orders Dashboard</h4>
        <ol class="breadcrumb">
            <li>
                <a href="{{ url(LOGIN_USER_TYPE.'/dashboard') }}"><i class="fa fa-dashboard"></i> Home </a>
            </li>
            <li class="active">
                <a href="{{ url(LOGIN_USER_TYPE.'/home_delivery_dashboard') }}"> Orders </a>
            </li>

        </ol>
    </section>

    <section class="content">

        <div class="box" >
            <div class="box-header with-border">
                <h4 class="box-title">Today's orders</h4>
                @if((LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status == 'Active') || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('create_delivery')))
                    <div style="float:right;"><a class="btn btn-success" href="{{ url(LOGIN_USER_TYPE.'/add_home_delivery') }}">Add Order</a></div>
                @endif
            </div>

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
                                <h3 class="panel-title"><strong>Searching for driver</strong> <span class="badge pull-right">{{$value->count()}}</span></h3>
                            @elseif ($key == 'assigned')
                                <h3 class="panel-title"><strong>En route to pick up</strong><span class="badge pull-right">{{$value->count()}}</span></h3>
                            @elseif ($key == 'picked_up')
                                <h3 class="panel-title"><strong>En route to drop off</strong><span class="badge pull-right">{{$value->count()}}</span></h3>
                            @elseif ($key == 'delivered')
                                <h3 class="panel-title"><strong>Delivered</strong><span class="badge pull-right">{{$value->count()}}</span></h3>
                            @endif
                            </div>
                            <div class="panel-body">
                                @foreach($value as $order)
                                        @php
                                            $accord += 1;
                                        @endphp
                                        <div class="row content box box-solid order-box" style="padding:0; min-height:unset;">
                                            @if ($time_now > $order->delivery_time && $key != 'delivered')
                                            <div class="col-lg-12 top-bottom-inner-buffer bg-warning">
                                            <div class="row">
                                                    <div class="col-lg-12">
                                                        <h5>Expired!</h5>
                                                    </div>
                                                </div>
                                            @else
                                            <div class="col-lg-12 top-bottom-inner-buffer">
                                            @endif
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <h4><span class="pull-left">Delivery #{{$order->id}}</span></h4>
                                                        <a href="#colps-{{$accord}}" data-toggle="collapse" aria-expanded="false" aria-controls="colps-{{$accord}}" class="pull-right">&nbsp;<i class="fa fa-chevron-circle-up fa-2x" aria-hidden="true"></i></a>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <small class="text-muted">{{ $order->merchant_name }}</small><br>
                                                        <small class="text-muted">Customer: {{ $order->customer_name }}</small><br>
                                                        <small class="text-muted">Deliver up to {{$order->delivery_time}}</small>
                                                    </div>
                                                </div>

                                                <div class="collapse" id="colps-{{$accord}}" aria-expanded="true">
                                                <hr>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                    <a href="{{url(LOGIN_USER_TYPE.'/home_delivery_orders/'.$order->id)}}" class="btn btn-xs btn-info" title="Order details"><i class="fa fa-eye"></i></a>&nbsp;
                                                    @if(LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('update_delivery'))
                                                        <a href="{{url(LOGIN_USER_TYPE.'/edit_home_delivery/'.$order->id)}}" class="btn btn-xs btn-primary" title="Edit order details"><i class="glyphicon glyphicon-edit"></i></a>
                                                    @endif
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-12 top-bottom-buffer">
                                                        @if ($key != 'new' && $key != 'delivered')
                                                        <h4><span class="label label-warning">ETA: <strong>{{$order->eta ? $order->eta : 0 }} minutes</strong></span></h4>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="row top-bottom-buffer higlight-element top-bottom-inner-buffer">
                                                    <div class="col-lg-9 col-xs-9top-bottom-buffer">
                                                        <small class="text-muted">Got at {{$order->created_at}}</small><br>
                                                        <small class="text-muted">Deliver up to {{$order->delivery_time}}</small>
                                                    </div>
                                                </div>
                                                <div class="row top-bottom-buffer higlight-element top-bottom-inner-buffer">
                                                    <div class="col-lg-2 col-xs-1 top-bottom-buffer">
                                                        <a href="{{url(LOGIN_USER_TYPE.'/edit_merchant/'.$order->merchant_id)}}" title="Edit Merchant"><i class="fa fa-user fa-2x text-success" aria-hidden="true"></i></a>
                                                    </div>
                                                    <div class="col-lg-10 col-xs-11">
                                                        <div class="row">
                                                            <div class="col-lg-12 top-bottom-buffer">
                                                                <span class="align-middle text-muted">Merchant: </span>
                                                                <span class="align-middle">{{ $order->merchant_name }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-12 top-bottom-buffer">
                                                                <i class="fa fa-map-marker pull-left" aria-hidden="true"></i>
                                                                <span class="align-middle">{{$order->pickup_location}}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row top-bottom-buffer higlight-element top-bottom-inner-buffer">
                                                    <div class="col-lg-2 col-xs-1 top-bottom-buffer">
                                                        <a href="{{url(LOGIN_USER_TYPE.'/edit_rider/'.$order->customer_id)}}" title="Edit Customer"><i class="fa fa-user-o fa-2x text-success" aria-hidden="true"></i></a>
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
                                                        <a href="{{url(LOGIN_USER_TYPE.'/edit_driver/'.$order->driver_id)}}" title="Edit driver"><i class="fa fa-user-circle-o text-info fa-2x" aria-hidden="true"></i></a>&nbsp;
                                                    </div>
                                                    <div class="col-lg-10 col-xs-11">
                                                        <div class="row">
                                                            <div class="col-lg-12 top-bottom-buffer">
                                                                <span class="align-middle text-muted">Driver: </span>
                                                                <span class="align-middle">{{ $order->first_name }}</span>
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
    </section>

</div>
@endsection
@push('scripts')
<script>
    var interval_id;

    function reload_page(){
        location.reload(true);
    }

    $(function() {
        $(window).focus(function() {
        if (!interval_id)
            interval_id = setInterval(reload_page, 60000);
        });
    });

    $(function() {
        $(window).blur(function() {
            clearInterval(interval_id);
            interval_id = 0;
        });
    });

</script>
@endpush