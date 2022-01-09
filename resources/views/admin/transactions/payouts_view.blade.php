@extends('admin.template')

@section('main')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Manage Payout transactions
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url(LOGIN_USER_TYPE.'/dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Payout transactions</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Manage Payout transactions</h3>
                        @if((LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status == 'Active') || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('manage_driver_payments')) || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('manage_company_payments')) || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('manage_transactions')))
                            <div style="float:right; margin: 0 5px;"><a class="btn btn-info" href="" data-toggle="modal" data-target="#confirm-payment"><i class="fa fa-dollar"></i> &nbsp; Custom payment</a></div>
                            <!-- Modal -->
                            <div class="modal fade" id="confirm-payment" tabindex="-1" role="dialog" aria-labelledby="confirm-payment" aria-hidden="true">
                                {!! Form::open(['url' => LOGIN_USER_TYPE.'/make_custom_payout', 'class' => 'form-horizontal','files' => true]) !!}
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                                <h4 class="modal-title" id="confirm-payment">Proceed payment</h4>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="redirect_url" value="admin/payout_transactions">
                                                <input type="hidden" name="currency" value="AUD">
                                                <input type="hidden" name="admin_user" value="{{ Auth::guard('admin')->user()->username }}">
                                                <div class="clearfix">
                                                    <div class="row pick-location clearfix">
                                                        <div class="col-md-12">
                                                            <input type="text" id="input-driver-id" name="driver" placeholder="Driver" value="" />
                                                            <span class="text-danger error_msg error_driver">{{ $errors->first('driver') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row clearfix">
                                                    <div class="col-md-12">
                                                        <label for="input_amount" class="control-label">Amount (AUD)<em class="text-danger">*</em></label>
                                                        {!! Form::number('amount', '', ['class' => 'form-control', 'id' => 'input_amount', 'placeholder' => '0.00', 'autocomplete' => 'off',"step" => "0.01"]) !!}
                                                        <span class="text-danger error_msg">{{ $errors->first('input_amount') }}</span>
                                                    </div>
                                                </div>
                                                <div class="row clearfix">
                                                    <div class="col-md-12">
                                                        <label for="input_description" class="control-label">Description<em class="text-danger">*</em></label>
                                                        {!! Form::text('Description', '', ['class' => 'form-control', 'id' => 'input_description', 'placeholder' => 'Payment description']) !!}
                                                        <span class="text-danger error_msg">{{ $errors->first('input_description') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default confirm-payment_cancel" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success btn-ok confirm-payment" name="submit" value="submit">Send</button>
                                            </div>
                                        </div>
                                    </div>
                                {!! Form::close() !!}
                            </div>
                        @endif
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        {!! $dataTable->table(['class' => 'table table-striped dataTable', 'style' => 'width: 100%;'], true) !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@push('scripts')
<link rel="stylesheet" href="{{ url('css/buttons.dataTables.css') }}">
<script src="{{ url('js/dataTables.buttons.js') }}"></script>
<script src="{{ url('js/buttons.server-side.js') }}"></script>
{!! $dataTable->scripts() !!}
<script>
    $(function() {
        $('#input-driver-id').selectize({
            plugins: ['remove_button'],
            maxItems: 1

        });
        init_drivers();
    });
    function init_drivers() {
        var select = $("#input-driver-id").selectize();
        var selectize = select[0].selectize;
        selectize.disable();

        $.ajax({
            type: 'GET',
            url: APP_URL + '/' + LOGIN_USER_TYPE + '/get_send_drivers',
            dataType: "json",
            success: function(resultData) {
                var select = $("#input-driver-id").selectize();
                var selectize = select[0].selectize;
                selectize.clear();
                selectize.clearOptions();
                $.each(resultData, function(key, value) {
                    selectize.addOption({ value: value.id, text: value.id + ' - ' +value.first_name + ' ' + value.last_name + ' (phone: +' + value.country_code + value.mobile_number + ')' });
                });
                selectize.enable();
            }
        });
    }
</script>
@endpush
