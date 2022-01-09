{{-- @extends('admin.template') --}}
@extends('template_merchant_site')
@section('main')

<style>
    /* Next & previous buttons */
    .bootstrap-datetimepicker-widget {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 14654;
    }
    .prev, .next {
        cursor: pointer;
        position: absolute;
        /* top: 50%; */
        display: block;
        width: auto;
        margin-top: -22px;
        padding: 16px;
        color: #5892fc;
        font-weight: bold;
        font-size: 28px;
        transition: 0.6s ease;
        border-radius: 0 3px 3px 0;
        user-select: none;
        z-index: 998;
    }
    .next {
        right: 0;
    }
    .steps {
        margin-bottom: 30px;
        position: relative;
        height: 25px;
    }

    .steps>div {
        position: absolute;
        top: -5px;
        -webkit-transform: translate(-50%);
        -ms-transform: translate(-50%);
        transform: translate(-50%);
        height: 25px;
        padding: 0 5px;
        display: inline-block;
        width: 80%;
        text-align: center;
        -webkit-transition: .3s all ease;
        transition: .3s all ease;
    }

    .steps>div>span {
        line-height: 25px;
        height: 25px;
        margin: 0;
        color: #444444;
        font-family: 'Roboto', sans-serif;
        font-size: 1.9rem;
        font-weight: 300;
        width: 80%;
        text-transform: uppercase;
    }

    .steps>div>.liner {
        position: absolute;
        height: 2px;
        width: 0%;
        left: 0;
        top: 50%;
        margin-top: -1px;
        background: #999;
        -webkit-transition: .3s all ease;
        transition: .3s all ease;
    }

    .step-one, .step-two, .step-three, .step-four, .step-five {
        left: 50%;
        
    }

    .line {
        height: 5px;
        background: #ddd;
        position: relative;
        border-radius: 10px;
        overflow: visible;
        margin-bottom: 50px;
    }

    .line .dot-move {
        position: absolute;
        top: 50%;
        left: 0%;
        width: 20px;
        height: 20px;
        -webkit-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        background: #5892fc;
        border-radius: 50%;
        -webkit-transition: .3s all ease;
        transition: .3s all ease;
    }

    .line .dot {
        cursor: pointer;
        position: absolute;
        top: 50%;
        width: 30px;
        height: 30px;
        left: 0;
        background: #ddd;
        border-radius: 50%;
        -webkit-transition: .3s all ease;
        transition: .3s all ease;
        -webkit-transform: translate(-50%, -50%) scale(.5);
        -ms-transform: translate(-50%, -50%) scale(.5);
        transform: translate(-50%, -50%) scale(.5);
    }

    .line .dot.zero {
        left: 0%;
        background: #bbb;
    }

    .container.slider-one-active .dot.zero {
        background: #5892fc;
    }

    .line .dot.two {
        left: 25%;
        background: #bbb
    }

    .line .dot.four {
        left: 75%;
        background: #bbb
    }

    .line .dot.center {
        left: 50%;
        background: #bbb
    }

    .line .dot.full {
        left: 100%;
        background: #bbb
    }

    .slider-ctr {
        width: 100%;
        overflow: hidden;
    }

    .slider {
        width: 100%;
        overflow: hidden;
        -webkit-transition: .3s all ease;
        transition: .3s all ease;
        -webkit-transform: translate(0px) scale(1);
        -ms-transform: translate(0px) scale(1);
        transform: translate(0px) scale(1);
    }

    .container.slider-one-active .slider-two,
    .container.slider-one-active .slider-three {
        -webkit-transform: scale(.5);
        -ms-transform: scale(.5);
        transform: scale(.5);
    }

    .container.slider-two-active .slider-one,
    .container.slider-two-active .slider-three {
        -webkit-transform: scale(.5);
        -ms-transform: scale(.5);
        transform: scale(.5);
    }

    .container.slider-three-active .slider-one,
    .container.slider-three-active .slider-two {
        -webkit-transform: scale(.5);
        -ms-transform: scale(.5);
        transform: scale(.5);
    }

    .slider-one,
    .slider-two,
    .slider-three {
        -webkit-transition: .3s all ease;
        transition: .3s all ease;
    }

    .slider-form {
        float: left;
        width: 400px;
        text-align: center;
    }

    .slider-form h2 {
        font-size: 1.5rem;
        font-family: 'Roboto', sans-serif;
        font-weight: 300;
        margin-bottom: 50px;
        color: #999;
        position: relative;
    }

    .slider-form h2 .yourname {
        font-weight: 400;
    }

    .slider-form h3 {
        font-size: 1.5rem;
        font-family: 'Roboto', sans-serif;
        font-weight: 300;
        margin-bottom: 50px;
        line-height: 1.5;
        color: #999;
        position: relative;
    }

    .slider-form h3 .balapa {
        font-family: 'Pacifico', sans-serif;
        display: inline-block;
        color: #5892fc;
        text-decoration: none
    }

    .slider-form [type="text"] {
        width: 100%;
        box-sizing: border-box;
        padding: 15px 20px;
        background: #fafafa;
        border: 1px solid transparent;
        color: #777;
        border-radius: 50px;
        margin-bottom: 50px;
        font-size: 1rem;
        font-family: 'Roboto', sans-serif;
        position: relative;
        z-index: 99;
    }

    .slider-form [type="text"]:focus {
        background: #fcfcfc;
        border: 1px solid #ddd;
    }

    .slider-form button,
    .reset {
        display: inline-block;
        text-decoration: none;
        background: #5892fc;
        border: none;
        color: white;
        padding: 10px 25px;
        font-size: 1rem;
        border-radius: 3px;
        cursor: pointer;
        font-family: 'Roboto', sans-serif;
        font-weight: 300;
        position: relative;
    }

    /*  emot */

    .label-ctr {
        margin-bottom: 50px;
    }

    label.radio {
        height: 55px;
        width: 55px;
        display: inline-block;
        margin: 0 10px;
        background: transparent;
        position: relative;
        border-radius: 50%;
        cursor: pointer
    }

    label.radio input {
        visibility: hidden
    }

    label.radio input:checked+.emot {
        -webkit-transform: scale(1.25);
        -ms-transform: scale(1.25);
        transform: scale(1.25);
    }

    label.radio input:checked+.emot,
    label.radio input:checked+.emot .mouth {
        border-color: #5892fc;
    }

    label.radio input:checked+.emot:before,
    label.radio input:checked+.emot:after {
        background: #5892fc;
    }

    label.radio .emot {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #fafafa;
        border-radius: 50%;
        border: 2px solid #ddd;
        -webkit-transition: .3s all ease;
        transition: .3s all ease;
    }

    label.radio .emot:before {
        content: "";
        position: absolute;
        top: 15px;
        left: 15px;
        width: 5px;
        height: 10px;
        background: #ddd;
    }

    label.radio .emot:after {
        content: "";
        position: absolute;
        top: 15px;
        right: 15px;
        width: 5px;
        height: 10px;
        background: #ddd;
    }

    label.radio .emot .mouth {
        position: absolute;
        bottom: 10px;
        right: 15px;
        left: 15px;
        height: 15px;
        border-radius: 50%;
        border: 3px solid #ddd;
        background: transparent;
        clip: rect(0, 35px, 10px, 0);
    }

    label.radio .emot .mouth.smile {
        -webkit-transform: rotate(180deg);
        -ms-transform: rotate(180deg);
        transform: rotate(180deg);
    }

    label.radio .emot .mouth.sad {
        -webkit-transform: translateY(50%);
        -ms-transform: translateY(50%);
        transform: translateY(50%);
    }

    /*	center */

    .container.center .line .dot-move {
        left: 50%;
        -webkit-animation: .3s anim 1;
    }

    .container.center .line .dot.center {
        background: #5892fc;
    }

    .container.center .slider {
        -webkit-transform: translate(-400px);
        -ms-transform: translate(-400px);
        transform: translate(-400px);
    }

    .container.center .step-two {
        clip: rect(0, 100px, 25px, 0px);
    }

    .container.center .step-one .liner {
        width: 100%;
    }

    /*	full */

    .container.full .line .dot-move {
        left: 100%;
        -webkit-animation: .3s anim 1;
    }

    .container.full .line .dot.full {
        background: #5892fc;
    }

    .container.full .slider {
        -webkit-transform: translate(-800px);
        -ms-transform: translate(-800px);
        transform: translate(-800px);
    }

    .container.full .step-two,
    .container.full .step-three {
        clip: rect(0, 100px, 25px, 0px);
    }

    .container.full .step-one .liner,
    .container.full .step-two .liner {
        width: 100%;
    }

    .add-container{
        background:hsl(0, 0%, 98%);
        padding-bottom: 2rem;
    }

</style>

<div class="containter-wrapper" ng-controller='delivery_order'>
    <div class="container-fluid add-container">
        <h3 class="tracking-header">Add delivery order</h3>
        <br>
        <br>
        <br>
        <div class="row justify-content-md-center">
            <div class="col col-sm-10 col-xs-10 col-sm-offset-1 col-xs-offset-1">
                <div class="slider-one-active row" id="slider-container">
                    <div class="changer row">
                        <a class="prev" id="previous-button" onclick="">&#10094;</a>
                        <a class="next" id="next-button" onclick="">&#10095;</a>
                    </div>
                    <div class="steps row">
                        <div class="step step-one active-step step-0" id="step-note-0"  style="display:none;">
                            <div class="liner"></div>
                            <span>Choose pick up and drop locations</span>
                        </div>
                        <div class="step step-two step-1" id="step-note-1" style="display:none;">
                            <div class="liner"></div>
                            <span>Select delivery time</span>
                        </div>
                        <div class="step step-three step-2" id="step-note-2" style="display:none;">
                            <div class="liner"></div>
                            <span>Add customer data</span>
                        </div>
                        <div class="step step-four step-3" id="step-note-3" style="display:none;">
                            <div class="liner"></div>
                            <span>Any notes for delivery?</span>
                        </div>
                        <div class="step step-five step-4" id="step-note-4" style="display:none;">
                            <div class="liner"></div>
                            <span>Summary</span>
                        </div>
                    </div>
                    <div class="line row">
                        <div class="dot-move"></div>
                        <div class="dot zero step-0" id="step-dot-0"></div>
                        <div class="dot two step-1" id="step-dot-1"></div>
                        <div class="dot center step-2" id="step-dot-2"></div>
                        <div class="dot four step-3" id="step-dot-3"></div>
                        <div class="dot full step-4" id="step-dot-4"></div>
                    </div>
                    <div class="slider-ctr row">
                        <div class="slider col col-sm-11 col-xs-11">
                            {!! Form::open(['method'=>'POST','url' => LOGIN_USER_TYPE.'/add_delivery', 'class' => 'form-horizontal delivery_adding','id'=>'delivery_order','name'=>'deliveryAddForm']) !!}
                            {!! Form::hidden('driver_id', '', ['id' => 'driver-id']) !!}
                            {!! Form::hidden('fee', '', ['class' => 'form-control', 'id' => 'input_fee', 'placeholder' => '0.00', 'autocomplete' => 'off',"step" => "0.01"]) !!}
                            <input class="form-control" type="hidden" id="input-merchant-id" name="merchant_id" placeholder="Merchant" value="{{$merchant_id}}" />
                            <div id="slider-0" style="display:none;">
                                <div ng-init='pick_up_latitude = ""' class="row pick-location">
                                    {!! Form::hidden('pick_up_latitude', '', ['id' => 'pick_up_latitude']) !!}
                                    {!! Form::hidden('pick_up_longitude', '', ['id' => 'pick_up_longitude']) !!}
                                    <div class="input-group">
                                        <span class="input-group-addon" id="basic-addon1"><img src="{{ url('/images/PinFrom.png') }}" alt="" srcset="" width="10" ></span>
                                        {!! Form::text('pick_up_location', $merchant_address, ['class' => 'form-control change_field', 'id' =>
                                    'input_pick_up_location', 'placeholder' => 'Pick Up Location', 'autocomplete' => 'off', 'aria-describedby'=>"basic-addon1"]) !!}
                                    </div>
                                    <span class="text-danger error_msg error_pick_up_location">{{ $errors->first('pick_up_location') }}</span>
                                </div>
                                <br>
                                <div class="row drop-location">
                                    {!! Form::hidden('drop_off_latitude', '', ['id' => 'drop_off_latitude']) !!}
                                    {!! Form::hidden('drop_off_longitude', '', ['id' => 'drop_off_longitude']) !!}
                                    <div class="input-group">
                                        <span class="input-group-addon" id="basic-addon2"><img src="{{ url('/images/PinTo.png') }}" alt="" srcset="" width="10" ></span>
                                        {!! Form::text('drop_off_location', '', ['class' => 'form-control change_field', 'id' =>
                                    'input_drop_off_location', 'placeholder' => 'Drop Off Location', 'autocomplete' => 'off', 'aria-describedby'=>"basic-addon2"]) !!}
                                    </div>
                                    <span class="text-danger error_msg error_drop_off_location">{{ $errors->first('drop_off_location') }}</span>
                                </div>
                                <br>
                                <div class="row clearfix">
                                    <div class="map-view clearfix">
                                        <div id="map"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="slider-1"  style="display:none;">
                                <div class="row clearfix">
                                    <div class="col-md-12" ng-init='date_time = ""'>
                                        {!! Form::hidden('estimate_time', '', ['id' => 'input_estimate_time']) !!}
                                        {!! Form::text('delivery_time','', ['class' => 'form-control change_field input-lg', 'id' => 'input_date_time',
                                        'placeholder' => 'Estimate time','ng-cloak']) !!}
                                        <span class="text-danger error_msg error_estimate_time">{{ $errors->first('estimate_time') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div id="slider-2"  style="display:none;">
                                <div class="row clearfix">
                                    <div class="col-md-3" ng-init="country_code={{($country_code_option[12]->phone_code)}}" style="display: none;">
                                        <select class='form-control selectpicker' style="display: none;" data-live-search="true" id="input_country_code" name='country_code' ng-model="country_code">
                                            @foreach($country_code_option as $country_code)
                                            <option value="{{@$country_code->phone_code}}">{{$country_code->long_name}}</option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger error_msg">{{ $errors->first('country_code') }}</span>
                                    </div>
                                </div>
                                <div class="row clearfix form-group">
                                        <div class="col-auto form-group m-0">
                                            {!! Form::hidden('customer_phone_number', '', ['id' => 'customer_phone_number']) !!}
                                            <div class="input-group">
                                                <span class="input-group-addon" id="basic-addon3">
                                                    <input type="text" disabled name="country_code_view" class='form-control input-lg' id='country_code_view' style="display: inline; width: 100px; height: 25px;">
                                                </span>
                                                {!! Form::text('mobile_number', '', ['class' => 'form-control input-lg', 'id' => 'input_mobile_number', 'placeholder' => 'Phone No', 'autocomplete' => 'off' , 'aria-describedby'=>"basic-addon3"]) !!}
                                            </div>
                                            <span class="text-danger error_msg error_mobile_number">{{ $errors->first('mobile_number') }}</span>
                                        </div>
                                </div>
                                <div class="row clearfix">
                                    <div class="col-auto form-group m-0">
                                        {!! Form::hidden('customer_name', '', ['id' => 'customer_name']) !!}
                                        {!! Form::text('first_name', '', ['class' => 'form-control input-lg', 'id' => 'input_first_name', 'placeholder'=> 'First Name', 'autocomplete' => 'off']) !!}
                                        <span class="text-danger error_msg error_first_name">{{ $errors->first('first_name') }}</span>
                                    </div>
                                </div>
                                <div class="row clearfix">
                                    <div class="col-auto form-group m-0">
                                        {!! Form::text('last_name', '', ['class' => 'form-control input-lg', 'id' => 'input_last_name', 'placeholder' => 'Last Name', 'autocomplete' => 'off']) !!}
                                        <span class="text-danger error_msg error_last_name">{{ $errors->first('last_name') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div id="slider-3"  style="display:none;">
                                <div class="row clearfix">
                                    <div class="col-md-12">
                                        {!! Form::textArea('order_description','', ['class' => 'form-control change_field', 'id' =>
                                        'input_order_description', 'placeholder' => 'Order description']) !!}
                                        <span class="text-danger error_msg error_last_name"></span>
                                    </div>
                                </div>
                            </div>
                            <div id="slider-4"  style="display:none;" class="row">
                                <div class="col-xs-12 summary-fields" id="summary-fields-container">
                                    <ul class="list-group">
                                        <li class="list-group-item">Pick up at:<div class="well well-sm" id="badge-pickup">Not filled yet</div></li>
                                        <li class="list-group-item">Drop at:<div class="well well-sm" id="badge-drop">Not filled yet</div></li>
                                        <li class="list-group-item">Delivery time:<div class="well well-sm" id="badge-time">Not filled yet</div></li>
                                        <li class="list-group-item">Customer name:<div class="well well-sm" id="badge-cname">Not filled yet</div></li>
                                        <li class="list-group-item">Customer phone:<div class="well well-sm" id="badge-cphone">Not filled yet</div></li>
                                        <li class="list-group-item">Order notes:<div class="well well-sm" id="badge-notes">Not filled yet</div></li>
                                    </ul>
                                </div>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key={{$map_key}}&sensor=false&libraries=places"></script>
<script src="{{ url('admin_assets/dist/js/merchant_order.js') }}"></script>
<script type="text/javascript">
    var REQUEST_URL = "{{url('/'.LOGIN_USER_TYPE)}}";
    // console.log(REQUEST_URL);
    var appTimezone = "{{ date_default_timezone_get() }}";
    var old_edit_date = "{{''}}"
    var page = "{{'new'}}"
</script>
@endpush
