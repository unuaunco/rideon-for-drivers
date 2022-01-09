@extends('admin.template')

@section('main')
 <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Manage Affiliate users
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="{{ url(LOGIN_USER_TYPE.'/dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Affiliate user</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">

          <div class="box">
            <div class="box-header">
                <h3 class="box-title">Manage Affiliate users</h3>
                @if((LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status == 'Active') || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('create_affiliate')))
                    <div style="float:right; margin: 0 5px;"><a class="btn btn-info" href="" data-toggle="modal" data-target="#confirm-affiliates-import">Import Affiliate users</a></div>
                    <!-- Modal -->
                    <div class="modal fade" id="confirm-affiliates-import" tabindex="-1" role="dialog" aria-labelledby="confirm-affiliates-import" aria-hidden="true">
                        {!! Form::open(['url' => LOGIN_USER_TYPE.'/import_affiliates', 'class' => 'form-horizontal','files' => true]) !!}
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title" id="confirm-affiliates-import">Proceed import</h4>
                                    </div>
                                    <div class="modal-body">
                                        <p>Upload file with affiliate data.</p>
                                        {!! Form::file('file',  ['class' => 'form-control', 'id' => "file", 'accept' => ".csv"]) !!}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default confirm-affiliates-import_cancel" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-ok confirm-affiliates-import" name="submit" value="submit">Import</button>
                                    </div>
                                </div>
                            </div>
                        {!! Form::close() !!}
                    </div>
                @endif
                @if((LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status == 'Active') || (LOGIN_USER_TYPE=='admin' && Auth::guard('admin')->user()->can('create_affiliate')))
                    <div style="float:right; margin: 0 5px;"><a class="btn btn-success" href="{{ url(LOGIN_USER_TYPE.'/add_affiliate') }}">Add Affiliate user</a></div>
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
@endpush
