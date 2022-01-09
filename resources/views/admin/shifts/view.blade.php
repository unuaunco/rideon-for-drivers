@extends('admin.template')

@section('main')
 <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Manage Shifts
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="{{ url(LOGIN_USER_TYPE.'/dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Shifts</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
                <h3 class="box-title">Manage Shifts</h3>
                @if((LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status == 'Active') || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('create_shift')))
                    <div style="float:right; margin: 0 5px;"><a class="btn btn-info" href="" data-toggle="modal" data-target="#confirm-shift-import">Import Shifts</a></div>
                    <!-- Modal importing -->
                    <div class="modal fade" id="confirm-shift-import" tabindex="-1" role="dialog" aria-labelledby="confirm-shift-import" aria-hidden="true">
                        {!! Form::open(['url' => LOGIN_USER_TYPE.'/import_shifts', 'class' => 'form-horizontal','files' => true]) !!}
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title" id="confirm-shift-import">Proceed import</h4>
                                    </div>
                                    <div class="modal-body">
                                        <p>Upload file with shifts data.</p>
                                        <p><strong>Format: </strong></p>
                                        <p>
                                            <span class="label label-info">driver_id</span>,
                                            <span class="label label-info">shift_start</span>(year-month-day hour-minute),
                                            <span class="label label-info">shift_end</span>(year-month-day hour-minute),
                                            <span class="label label-info">amount</span> ($)
                                        </p>
                                        {!! Form::file('file',  ['class' => 'form-control', 'id' => "file", 'accept' => ".csv"]) !!}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default confirm-shift-import_cancel" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-ok confirm-shift-import" name="submit" value="submit">Import</button>
                                    </div>
                                </div>
                            </div>
                        {!! Form::close() !!}
                    </div>
                @endif
                @if((LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status == 'Active') || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('create_shift')))
                    <div style="float:right; margin: 0 5px;"><a class="btn btn-success" id="shift-add-button" href="" data-toggle="modal" data-target="#confirm-shift-add">Add Shift</a></div>
                    {{-- Modal Shift add --}}
                    <div class="modal fade" id="confirm-shift-add" tabindex="-1" role="dialog" aria-labelledby="confirm-shift-add" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title" id="confirm-shift-add">Add new shift</h4>
                                </div>
                                <div class="modal-body">
                                {!! Form::open(['url' => LOGIN_USER_TYPE.'/add_shift', 'class' => 'form-horizontal','files' => true]) !!}
                                    {!! Form::hidden('status', 'scheduled', ['id' => 'shift-status']) !!}
                                    {!! Form::hidden('currency', '4', ['id' => 'shift-currency']) !!}
                                    <div class="box-body ed_bld">
                                        <span class="text-danger">(*)Fields are Mandatory</span>

                                        <div class="form-group">
                                            <label for="input_driver_id" class="col-sm-3 control-label">Driver<em class="text-danger">*</em></label>
                                            <div class="col-sm-6">
                                                <input type="text" id="input-driver-id" name="driver_id" placeholder="Driver" value="" />
                                                <span class="text-danger error_msg error_driver_id">{{ $errors->first('driver_id') }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="input_shift_start" class="col-sm-3 control-label">Shift Start<em class="text-danger">*</em></label>
                                            <div class="col-sm-6">
                                                {!! Form::text('shift_start', '', ['class' => 'form-control', 'id' => 'input_shift_start', 'placeholder' => 'Shift Start Time']) !!}
                                                <span class="text-danger">{{ $errors->first('shift_start') }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="input_shift_end" class="col-sm-3 control-label">Shift End<em class="text-danger">*</em></label>
                                            <div class="col-sm-6">
                                                {!! Form::text('shift_end', '', ['class' => 'form-control', 'id' => 'input_shift_end', 'placeholder' => 'Shift End Time']) !!}
                                                <span class="text-danger">{{ $errors->first('shift_end') }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="input_amount" class="col-sm-3 control-label">Amount<em class="text-danger">*</em></label>
                                            <div class="col-sm-6">
                                                {!! Form::number('amount', '', ['class'=>'form-control', 'id' => 'input_amount', 'rows' => 2, 'cols' =>40, "step" => "0.1"]) !!}
                                                <span class="text-danger">{{ $errors->first('amount') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer">
                                        <button type="button" class="btn btn-default confirm-shift-add_cancel pull-left" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-ok confirm-shift-add pull-right" name="submit" value="submit">Submit</button>
                                    </div>
                                </div>
                                {!! Form::close() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            <!-- /.box-header -->
            <div class="box-body">
                {!! $dataTable->table() !!}
            </div>
          </div>
        </div>
      </div>
    </section>
</div>

<!-- Modal edit -->
<div class="modal fade" id="confirm-shift-edit" tabindex="-1" role="dialog" aria-labelledby="confirm-shift-edit" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="confirm-shift-edit-label">Add new shift</h4>
            </div>
            <div class="modal-body">
            {!! Form::open(['url' => LOGIN_USER_TYPE.'/edit_shift', 'class' => 'form-horizontal','files' => true, 'id'=>'edit-shift-form']) !!}
                {!! Form::hidden('status', 'scheduled', ['id' => 'shift-status-edit']) !!}
                {!! Form::hidden('currency', '4', ['id' => 'shift-currency-edit']) !!}
                <div class="box-body ed_bld">
                    <span class="text-danger">(*)Fields are Mandatory</span>

                    <div class="form-group">
                        <label for="input-driver-id-edit" class="col-sm-3 control-label">Driver<em class="text-danger">*</em></label>
                        <div class="col-sm-6">
                            <input type="text" id="input-driver-id-edit" name="driver_id" placeholder="Driver" value="" />
                            <span class="text-danger error_msg error_driver_id">{{ $errors->first('driver_id') }}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="input_shift_start-edit" class="col-sm-3 control-label">Shift Start<em class="text-danger">*</em></label>
                        <div class="col-sm-6">
                            {!! Form::text('shift_start', '', ['class' => 'form-control', 'id' => 'input_shift_start-edit', 'placeholder' => 'Shift Start Time']) !!}
                            <span class="text-danger">{{ $errors->first('shift_start') }}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="input_shift_end-edit" class="col-sm-3 control-label">Shift End<em class="text-danger">*</em></label>
                        <div class="col-sm-6">
                            {!! Form::text('shift_end', '', ['class' => 'form-control', 'id' => 'input_shift_end-edit', 'placeholder' => 'Shift End Time']) !!}
                            <span class="text-danger">{{ $errors->first('shift_end') }}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="input_amount-edit" class="col-sm-3 control-label">Amount<em class="text-danger">*</em></label>
                        <div class="col-sm-6">
                            {!! Form::number('amount', '', ['class'=>'form-control', 'id' => 'input_amount-edit', 'rows' => 2, 'cols' =>40, "step" => "0.1"]) !!}
                            <span class="text-danger">{{ $errors->first('amount') }}</span>
                        </div>
                    </div>
                    
                </div>
                <div class="box-footer">
                    <button type="button" class="btn btn-default confirm-shift-add_cancel pull-left" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-ok confirm-shift-add pull-right" name="submit" value="submit">Submit</button>
                </div>
            </div>
            {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<link rel="stylesheet" href="{{ url('css/buttons.dataTables.css') }}">
<script src="{{ url('js/dataTables.buttons.js') }}"></script>
<script src="{{ url('js/buttons.server-side.js') }}"></script>
{!! $dataTable->scripts() !!}
<script>
    function init_drivers(input_id) {
        var select = $(input_id).selectize({
            plugins: ['remove_button'],
            maxItems: 1
        });
        var selectize = select[0].selectize;
        selectize.disable();

        $.ajax({
            type: 'GET',
            url: APP_URL + '/' + LOGIN_USER_TYPE + '/get_send_drivers',
            dataType: "json",
            success: function(resultData) {
                var currVal = $(input_id).val();
                var select = $(input_id).selectize();
                var selectize = select[0].selectize;
                selectize.clear();
                selectize.clearOptions();
                $.each(resultData, function(key, value) {
                    selectize.addOption({ value: value.id, text: value.id + ' - ' +value.first_name + ' ' + value.last_name + ' (phone: +' + value.country_code + value.mobile_number + ')' });
                });
                selectize.enable();

                if (currVal)
                    selectize.setValue(currVal, false);
            }
        });
    }

    function get_shift(shift_id) {

        $.ajax({
            type: 'GET',
            url: APP_URL + '/' + LOGIN_USER_TYPE + '/edit_shift/' + shift_id,
            dataType: "json",
            success: function(resultData) {
                console.log(resultData);
                $('#input_amount-edit').val(resultData.amount);
                $('#input_shift_end-edit').val(resultData.shift_end);
                $('#input_shift_start-edit').val(resultData.shift_start);
                $('#input-driver-id-edit').val(resultData.driver_id);
                $('#shift-currency-edit').val(resultData.currency);
                $('#shift-status-edit').val(resultData.status);
                init_drivers("#input-driver-id-edit");
                init_datepickers('#input_shift_start-edit', '#input_shift_end-edit');
            }
        });
    }

    //datetime picker
    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    function init_datepickers(input_from, input_to) {
        moment.tz.setDefault('Australia/Melbourne');

        var today = new Date().toLocaleString("en-US", { timeZone: "Australia/Melbourne" });

        $(input_from).datetimepicker({
            format: 'YYYY-MM-DD HH:mm',
            ignoreReadonly: true,
            sideBySide: true,
        }).on('dp.hide', function(e) {
            $(input_from).data("DateTimePicker").minDate(formatDate(today))
        });

        $(input_to).datetimepicker({
            format: 'YYYY-MM-DD HH:mm',
            ignoreReadonly: true,
            sideBySide: true,
        }).on('dp.hide', function(e) {
            $(input_to).data("DateTimePicker").minDate(formatDate(today))
        });
    }

    $('#shift-add-button').on('click', function(){
        init_drivers("#input-driver-id");
        init_datepickers('#input_shift_start', '#input_shift_end');
    });

    $('#shifts-table').on('draw.dt', function(){
        $('.edit-shift-btn').on('click', function(event){
            shift_id = $(this).attr('data-href').replace(APP_URL + '/' + LOGIN_USER_TYPE + '/edit_shift/', '');
            get_shift(shift_id);
            $('#edit-shift-form').attr('action', APP_URL + '/' + LOGIN_USER_TYPE + '/edit_shift/' + shift_id);
        });
    });

    
</script>
@endpush
