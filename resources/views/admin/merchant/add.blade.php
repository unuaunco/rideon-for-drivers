@extends('admin.template')
@section('main')
<div class="content-wrapper" ng-controller="driver_management">
	<section class="content-header">
		<h1> Add Merchant </h1>
		<ol class="breadcrumb">
			<li>
				<a href="{{ url(LOGIN_USER_TYPE.'/dashboard') }}"> <i class="fa fa-dashboard"></i> Home </a>
			</li>
			<li>
				<a href="{{ url(LOGIN_USER_TYPE.'/merchants') }}"> Merchants </a>
			</li>
			<li class="active"> Add </li>
		</ol>
	</section>
	<section class="content">
		<div class="row">
			<div class="col-md-8 col-sm-offset-2 ne_ed">
				<div class="box box-info">
					<div class="box-header with-border">
						<h3 class="box-title">Add Merchant Form</h3>
					</div>
					{!! Form::open(['url' => LOGIN_USER_TYPE.'/add_merchant', 'class' => 'form-horizontal','files' => true]) !!}
					<div class="box-body ed_bld">
						<span class="text-danger">(*)Fields are Mandatory</span>
						<div class="form-group">
							<label for="input_name" class="col-sm-3 control-label">Merchant Name<em class="text-danger">*</em></label>
							<div class="col-sm-6">
								{!! Form::text('name', '', ['class' => 'form-control', 'id' => 'input_name', 'placeholder' => 'Name']) !!}
								<span class="text-danger">{{ $errors->first('name') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_description" class="col-sm-3 control-label">Description<em class="text-danger">*</em></label>
							<div class="col-sm-6">
                                {!! Form::textarea('description', null, ['class'=>'form-control', 'id' => 'input_description', 'rows' => 2, 'cols' => 40, 'placeholder' => 'Long description']) !!}
								<span class="text-danger">{{ $errors->first('description') }}</span>
							</div>
                        </div>
                        <div class="form-group">
							<label for="input_base_fee" class="col-sm-3 control-label">Base Fee<em class="text-danger">*</em></label>
							<div class="col-sm-6">
                                {!! Form::number('base_fee', '8.95', ['class'=>'form-control', 'id' => 'input_base_fee', 'rows' => 2, 'cols' => 40, "step" => "0.01", 'placeholder' => '8.95']) !!}
								<span class="text-danger">{{ $errors->first('base_fee') }}</span>
							</div>
                        </div>
                        <div class="form-group">
							<label for="input_base_distance" class="col-sm-3 control-label">Base Distance, KM<em class="text-danger">*</em></label>
							<div class="col-sm-6">
                                {!! Form::number('base_distance', '5.00', ['class'=>'form-control', 'id' => 'input_base_distance', 'rows' => 2, 'cols' => 40, "step" => "0.01", 'placeholder' => '5.00']) !!}
								<span class="text-danger">{{ $errors->first('base_distance') }}</span>
							</div>
                        </div>
                        <div class="form-group">
							<label for="input_surchange_fee" class="col-sm-3 control-label">Surchange Fee, per KM<em class="text-danger">*</em></label>
							<div class="col-sm-6">
                                {!! Form::number('surchange_fee', '1.00', ['class'=>'form-control', 'id' => 'input_surchange_fee', 'rows' => 2, 'cols' => 40, "step" => "0.01", 'placeholder' => '1.00']) !!}
								<span class="text-danger">{{ $errors->first('surchange_fee') }}</span>
							</div>
                        </div>
						<div class="form-group">
							<label for="input_cuisine_type" class="col-sm-3 control-label">Type of Cuisine<em class="text-danger">*</em></label>
							<div class="col-sm-6">
								{!! Form::text('cuisine_type', '', ['class' => 'form-control', 'id' => 'input_cuisine_type', 'placeholder' => 'Type of Cuisine']) !!}
								<span class="text-danger">{{ $errors->first('cuisine_type') }}</span>
							</div>
						</div>
                        <div class="form-group" style="margin-bottom: 1em">
                            <label for="input_integration_type" class="col-sm-3 control-label">Integration Type</label>
                            <div class="col-sm-6">
                                {!! Form::select('integration_type', $integrations, '1', ['class'=>'form-control', 'id' => 'input_integration_type']) !!}
                                <span class="text-danger">{{ $errors->first('integration_type') }}</span>
                           </div>
						</div>
						<div class="form-group" id="shared_secret_section" style="display:none">
							<label for="input_shared_secret" class="col-sm-3 control-label">Shared Secret<em class="text-danger">*</em></label>
							<div class="col-sm-6">
								{!! Form::text('shared_secret', '', ['class' => 'form-control', 'id' => 'input_shared_secret', 'placeholder' => 'Shared Secret']) !!}
								<span class="text-danger">{{ $errors->first('shared_secret') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_status" class="col-sm-3 control-label">Country Code<em class="text-danger">*</em></label>
							<div class="col-sm-4" ng-init="country_code={{($country_code_option[12]->phone_code)}}">
								<select class ='form-control' id = 'input_country_code' name='country_code' ng-model="country_code">
									<option value="" disabled=""> Select </option>
									@foreach($country_code_option as $country_code)
									<option value="{{@$country_code->phone_code}}" <?php if (old('country_code') == $country_code->phone_code) echo 'selected'; ?>>{{$country_code->long_name}}</option>
									@endforeach
								</select>
								<span class="text-danger">{{ $errors->first('country_code') }}</span>
							</div>							
							<div class="col-sm-2">
								<input type="text" disabled name="country_code_view" class ='form-control' id = 'country_code_view'>
							</div>
						</div>
						<div class="form-group">
							<label for="input_mobile_number" class="col-sm-3 control-label">Mobile Number</label>
							<div class="col-sm-6">
								{!! Form::text('mobile_number','', ['class' => 'form-control', 'id' => 'mobile_number', 'placeholder' => 'Mobile Number']) !!}
								<span class="text-danger">{{ $errors->first('mobile_number') }}</span>
							</div>
						</div>
                        <div class="form-group">
							<label for="input_password" class="col-sm-3 control-label">Password</label>
							<div class="col-sm-6">
								{!! Form::password('password', ['id' => 'password', "class" => "form-control login-field-password", "autocomplete" => "off"]) !!}
								<span class="text-danger">{{ $errors->first('password') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_first_name" class="col-sm-3 control-label">First Name<em class="text-danger">*</em></label>
							<div class="col-sm-6">
								{!! Form::text('first_name', '', ['class' => 'form-control', 'id' => 'input_first_name', 'placeholder' => 'First Name']) !!}
								<span class="text-danger">{{ $errors->first('first_name') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_last_name" class="col-sm-3 control-label">Last Name<em class="text-danger">*</em></label>
							<div class="col-sm-6">
								{!! Form::text('last_name', '', ['class' => 'form-control', 'id' => 'input_last_name', 'placeholder' => 'Last Name']) !!}
								<span class="text-danger">{{ $errors->first('last_name') }}</span>
							</div>
                        </div>
						<div class="form-group" style="margin-bottom: 1em">
							<label for="input-tags3" class="col-sm-3 control-label">Used Referral Code</label>
							<div class="col-sm-6">
								{!! Form::hidden('referrer_id', '', ['class' => 'form-control', 'id' => 'referrer_id', 'placeholder' => 'referrer id']) !!}
								<input type="text" id="input-tags3" value=""/>						
							</div>
						</div>
						<div class="form-group">
							<label for="input_email" class="col-sm-3 control-label">Email<em class="text-danger">*</em></label>
							<div class="col-sm-6">
								{!! Form::text('email', '', ['class' => 'form-control', 'id' => 'input_email', 'placeholder' => 'Email']) !!}
								<span class="text-danger">{{ $errors->first('email') }}</span>
							</div>
						</div>
						{!! Form::hidden('user_type','Merchant', ['class' => 'form-control', 'id' => 'user_type', 'placeholder' => 'Select']) !!}										
						<div class="form-group">
							<label for="input_status" class="col-sm-3 control-label">Address Line 1<em class="text-danger">*</em></label>
							<div class="col-sm-6">
								{!! Form::text('address_line1','', ['class' => 'form-control', 'id' => 'address_line1', 'placeholder' => 'Address Line 1']) !!}
								<span class="text-danger">{{ $errors->first('address_line1') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_status" class="col-sm-3 control-label">Address Line 2</label>
							<div class="col-sm-6">
								{!! Form::text('address_line2','', ['class' => 'form-control', 'id' => 'address_line2', 'placeholder' => 'Address Line 2']) !!}
								<span class="text-danger">{{ $errors->first('address_line2') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_status" class="col-sm-3 control-label">City</label>
							<div class="col-sm-6">
								
								{!! Form::text('city','', ['class' => 'form-control', 'id' => 'city', 'placeholder' => 'City']) !!}
								<span class="text-danger">{{ $errors->first('city') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_status" class="col-sm-3 control-label">State</label>
							<div class="col-sm-6">								
								{!! Form::text('state','', ['class' => 'form-control', 'id' => 'state', 'placeholder' => 'State']) !!}
								<span class="text-danger">{{ $errors->first('state') }}</span>
							</div>
						</div>
						<div class="form-group">
							<label for="input_status" class="col-sm-3 control-label">Postal Code</label>
							<div class="col-sm-6">
								{!! Form::text('postal_code','', ['class' => 'form-control', 'id' => 'postal_code', 'placeholder' => 'Postal Code']) !!}
								<span class="text-danger">{{ $errors->first('postal_code') }}</span>
							</div>
						</div>
					</div>
					<div class="box-footer">
						<button type="submit" class="btn btn-info pull-right" name="submit" value="submit">Submit</button>
						<button type="submit" class="btn btn-default pull-left" name="cancel" value="cancel">Cancel</button>
					</div>
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</section>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	var REQUEST_URL = "{{url('/'.LOGIN_USER_TYPE)}}"; 
</script>
<script src="{{ url('js/selectize.js') }}"></script>
<script>
    $('#password').hideShowPassword({
        innerToggle: true
    });

	function OperationWithIntegrationType(integration_type) 
	{
		switch (integration_type)
		{
			case '1': // Gloria Food
				$('#shared_secret_section').hide();
				break;
			case '2': // Square Up
				$('#shared_secret_section').show();
				break;
			case '3': // Shopify
				$('#shared_secret_section').show();
				break;
            case '4': // CloudWaitress
				$('#shared_secret_section').show();
				break;
            case '5': // Yelo
				$('#shared_secret_section').show();
				break;
		}
	}

	$('#input_integration_type').change(function() {
		OperationWithIntegrationType($(this).val());
	});

	$(function() {
		OperationWithIntegrationType($('#input_integration_type').val());
			
		$('#input-tags3').selectize({
		    plugins: ['remove_button'],
		    maxItems: 1    		
		});
		init_user();
	})
	function init_user()
	{
		var usertype= 'all';
		var select = $("#input-tags3").selectize();
		var selectize = select[0].selectize;
		selectize.disable();
		
		$.ajax({
			type: 'POST',
			url: APP_URL+'/{{LOGIN_USER_TYPE}}/get_send_users',
			data: "type="+usertype,
			dataType: "json",
			success: function(resultData) {
				var select = $("#input-tags3").selectize();
				var selectize = select[0].selectize;
				selectize.clear();
				selectize.clearOptions();
				$.each(resultData, function (key, value) {
				if (value.user_type.toLowerCase() == 'driver')
					selectize.addOption({value:value.id,text:value.first_name + ' ' +  value.last_name+' - ' + value.user_type + ' - ' + value.mobile_number + ' - ' + value.referral_code});
				});
				selectize.enable();
				
				if(v = $("#referrer_id").val())
					selectize.setValue(v, false);
			}
		});
	}

</script>

@endpush

