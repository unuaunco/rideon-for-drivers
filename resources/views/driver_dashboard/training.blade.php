<title>Training</title>
@extends('template_driver_dashboard_new') 
@section('main')
<script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
  <script>
    var OneSignal = window.OneSignal || [];
    OneSignal.push(function() {
      OneSignal.init({
        appId: "6efaf3e7-e8c8-45ee-b36a-359a2a0fb6de",
        notifyButton: {
          enable: true,
        },
      });
      OneSignal.showNativePrompt();
    });
  </script>
<div class="page-content" style="padding:0px !important;" ng-controller="facebook_account_kit">
  @include('common.driver_dashboard_header_new')
  
</div>
      
      
    


  
</div>


</main>

@stop