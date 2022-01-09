@extends('admin.template')

@section('main')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Payment and Stripe statistics
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url(LOGIN_USER_TYPE.'/dashboard') }}"><i class="fa fa-dashboard"></i>&nbsp;Home</a></li>
            <li class="active">Payment and Stripe statistics</li>
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
                        @endif
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="small-box bg-dblue">
                                    <div class="inner">
                                        <h3>  {{(float)$available_funds['amount']/100}} {{strtoupper($available_funds['currency'])}}</h3>
                                        <p>Available funds on Stripe</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-dollar"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">&nbsp;</a>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-8 col-lg-6">
                                <div class="box box-solid bg-green-gradient">
                                    <div class="box-header ui-sortable-handle">
                                        <i class="fa fa-calendar"></i>

                                        <h3 class="box-title">Current Calculation cycle</h3>
                                        <div class="pull-right box-tools">
                                        <button type="button" class="btn btn-success btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
                                        </button>
                                        </div>
                                    </div>
                                    <div class="box-body no-padding">
                                        <div id="calendar" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-4 col-lg-3">
                                <div class="small-box bg-red">
                                    <div class="inner">
                                        <h3>  {{(float)$available_funds['amount']/100}} {{strtoupper($available_funds['currency'])}}</h3>
                                        <p>Total Payouts to Drivers</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-cab"></i>
                                    </div>
                                    <a href="#" class="small-box-footer">&nbsp;</a>
                                </div>
                                <div class="small-box bg-green">
                                    <div class="inner">
                                        <h3>  {{(float)$available_funds['amount']/100}} {{strtoupper($available_funds['currency'])}}</h3>
                                        <p>Paid Amount to Drivers</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-cab"></i>
                                    </div>
                                    <a href="{{ url(LOGIN_USER_TYPE.'/payout_transactions') }}" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function addDays(date, days) {
        const copy = new Date(Number(date));
        copy.setDate(date.getDate() + days);
        return copy;
    }
    function subDays(date, days) {
        const copy = new Date(Number(date));
        copy.setDate(date.getDate() - days);
        return copy;
    }
    $( function() {
        
        $( "#calendar" ).datepicker({
            weekStart: 1,
            maxViewMode: 0,
            todayHighlight: true,
            calendarWeeks: true,
            beforeShowDay: function(date){
                if (date.getMonth() == (new Date()).getMonth()){
                    let startDay = 5;
                    let endDay = 4;
                    let today = (new Date());
                    let rngStart = null;
                    let rngEnd = null;

                    if(today.getDay() >= startDay){
                        rngStart = subDays(today, startDay - today.getDay() + 7);
                        rngEnd = addDays(today, startDay - today.getDay() - 1);
                    }
                    else{
                        rngStart = subDays(today, endDay - today.getDay() - 7);
                        rngEnd = addDays(today, endDay - today.getDay() + 1);
                    }

                    if(date.getDate() >= rngStart.getDate() && date.getDate() <= rngEnd.getDate()){
                        return {
                                classes: 'active'
                            };
                    }

                }
            },
        });
    } );
</script>
@endpush