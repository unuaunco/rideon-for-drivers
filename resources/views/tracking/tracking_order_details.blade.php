@extends('template_without_header_footer')
@section('main')

<div class="content-wrapper row">
    <section class="content-header container-fluid text-center">
        <a href="{{ url('/tracking') }}">
          <img class="dash-head-logo" src="{{url(PAGE_LOGO_URL)}}">
        </a>
        <h3 class="tracking-header">Your Order Tracking</h3>
    </section>
    <section class="content box col-md-6 col-md-offset-3 box-solid tracking-box">
        <div class="box">
            <div class="box-header with-border text-center bg-deep-blue box-status" style="position: relative;">
                <h3 style="height: 100%; display: inline-block; margin: 2% 0; position: relative;"><strong>Status: {{$tracking_details->direction_status}}</strong></h3>
            </div>
            <div class="box-body">
                <div class="row" style="margin: 5% 0;">
                    <div class="col-md-8">
                        <p>Estimate Time To Arrive: <strong>{{$tracking_details->estimate_deliver_time}}</strong></p>
                        <p>Driver Name is <strong>{{ $tracking_details->driver_name }}</strong></p>
                    </div>
                    <div class="col-md-4 text-center"><img width="150" src="{{$tracking_details->driver_photo}}" alt="Driver photo"></div>
                </div>    
                <div class="clearfix" ng-controller='tracking'>
                    <div class="location-form">
                        <div class="row pick-location clearfix">
                            <div class="col-md-12" ng-init='pick_up_latitude = ""'>
                                {!! Form::hidden('pick_up_latitude',
                                @$tracking_details->pickup_latitude,
                                ['id' => 'pick_up_latitude']) !!}
                                {!! Form::hidden('pick_up_longitude',
                                @$tracking_details->pickup_longitude, ['id' => 'pick_up_longitude'])
                                !!}
                                {!! Form::hidden('pick_up_location',
                                @$tracking_details->pickup_location,
                                ['class' => 'form-control change_field', 'id' =>
                                'input_pick_up_location', 'placeholder' => 'Pick Up Location',
                                'autocomplete' => 'off']) !!}
                            </div>
                        </div>
                        <div class="row drop-location clearfix">
                            <div class="col-md-12">
                                {!! Form::hidden('drop_off_latitude',
                                @$tracking_details->drop_latitude,
                                ['id' => 'drop_off_latitude']) !!}
                                {!! Form::hidden('drop_off_longitude',
                                @$tracking_details->drop_longitude, ['id' => 'drop_off_longitude'])
                                !!}
                                {!! Form::hidden('drop_off_location',
                                @$tracking_details->drop_location,
                                ['class' => 'form-control change_field', 'id' =>
                                'input_drop_off_location', 'placeholder' => 'Drop Off Location',
                                'autocomplete' => 'off']) !!}
                            </div>
                        </div>
                            <div class="row driver-location clearfix">
                            <div class="col-md-12">
                                {!! Form::hidden('driver_latitude',
                                @$tracking_details->driver_latitude,
                                ['id' => 'driver_latitude']) !!}
                                {!! Form::hidden('driver_longitude',
                                @$tracking_details->driver_longitude, ['id' => 'driver_longitude'])
                                !!}
                                {!! Form::hidden('direction',
                                @$tracking_details->direction_status,
                                ['id' => 'direction']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="map-route-option">
                        <div class="map-view clearfix" style="heigh: 600px;">
                            <div id="map"></div>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin: 5% 0;">
                <div class="col-md-8">
                    <p>Order # : {{$tracking_details->order_id}}</p>
                    <p>Customer Name : {{ $tracking_details->full_name }}</p>
                    <p>Merchant : {{ $tracking_details->merchant_name }}</p>  
                    <p>Drop location : {{$tracking_details->drop_location}}</p>
                </div>
                </div>
            </div>
        </div>
    </section>
</div>

@endsection