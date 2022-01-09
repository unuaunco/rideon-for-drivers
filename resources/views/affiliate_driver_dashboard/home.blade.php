<title>Home</title>
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
  @include('common.affiliate_driver_dashboard_header_new')
  
 
  <div id="homeMainWrp">
      <div style="width: 100%; display: flex; justify-content: space-between;">
       
        <div class="newDashCardWrp" style="width: 25%;">
          <span style="font-size: 130%; font-weight: bold">Merchants</span>
          <div style="display: flex; flex-direction: column; align-items: center">
            <b style="font-size: 400%; margin-top: 0.2em; margin-bottom: 0.2em; font-family: 'MontserratBold'"><?php echo $merchantCount; ?></b>
            <span style="font-size: 100%; "><!-- +1 this week --></span>
          </div>
        </div>
 
      </div>
  
</div>


</main>

@stop