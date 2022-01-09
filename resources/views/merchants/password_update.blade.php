<title>Update Password</title>
@extends('template_merchant_site')
@section('main')

<style>
    .bg-accent {
        background-color: hsl(0, 0%, 98%);
        height: 80vh;
    }
    .center-flex {
        display: flex;
        justify-content: left;
    }
</style>

<div class="containter-wrapper bg-accent">
    <div class="container-fluid add-container">
        <h3 class="tracking-header">Update password</h3>
        <br>
        <div class="row center-flex">
            <div class="col col-sm-6 col-xs-12">
                {{ Form::open(array('url' => 'merchants/update_password','id'=>'form','class' => 'layout layout--flush','name'=>'merchant_pass')) }}


                <input type="hidden" name="user_type" value="{{ $result->user_type }}">
                <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="code" id="code" />
                <input type="hidden" id="user_id" name="user_id" value="{{ $result->id }}">
                
                <span class="text-danger error_msg">{{ $errors->first('password') }}</span>

                <div class="form-group">
                        <label for="currPass" class="col-auto control-label">Current password</label>
                        {!! Form::password('currPass', ['id' => 'currPass', "class" => "form-control
                        login-field-password", "autocomplete" => "off"]) !!}
                        <span class="text-danger error_msg error_pick_currPass">{{ $errors->first('currPass') }}</span>
                </div>
                <div class="form-group">
                        <label for="pass1" class="col-auto control-label">New password</label>
                        {!! Form::password('pass1', ['id' => 'pass1', "class" => "form-control
                        login-field-password", "autocomplete" => "off"]) !!}
                        <span class="text-danger error_msg error_pass1">{{ $errors->first('pass1') }}</span>
                </div>
                <div class="form-group">
                        <label for="pass2" class="col-auto control-label">Confirm new password</label>
                        {!! Form::password('pass2', ['id' => 'pass2', "class" => "form-control
                        login-field-password", "autocomplete" => "off"]) !!}
                        <span class="text-danger error_msg error_pass2">{{ $errors->first('pass2') }}</span>
                </div>
                
                <div class="form-group">
                        <button type="submit" class="btn btn-blue btn--primary btn-lg btn-block" id="update_btn">{{trans('messages.user.update')}}</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

@endsection