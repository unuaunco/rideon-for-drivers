 <title>Map</title>
@extends('template_driver_dashboard_new') 
@section('main')
<div class="col-lg-9 col-md-9 col-sm-12 col-xs-12 flexbox__item four-fifths page-content" style="padding:0px !important;" ng-controller="facebook_account_kit">
  @include('common.affiliate_driver_dashboard_header_new')
  <div style="padding: 1em; width: 100%; height: 80%"> <iframe style="height: 100%; width: 100%" src="https://www.google.com/maps/d/embed?mid=1MISfBSe75YjIoKgTuGT-boIbI-C9IgB-"></iframe>
  </div>
@stop