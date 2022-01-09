<title>Home page</title>
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
  
 
  <div id="homeMainWrp">
      <div style="width: 100%; display: flex; justify-content: space-between;">
          <div class="newDashCardWrp">
          <span style="font-size: 130%; font-weight: bold">Deliveries</span>
          <div style="display: flex; flex-direction: column; align-items: center">
            <b style="font-size: 400%; margin-top: 0.2em; margin-bottom: 0.2em; font-family: 'MontserratBold'"><?php echo $deliveries; ?></b>
            <span style="font-size: 100%; ">+1 this week</span>
          </div>
          
        </div>
        <div class="newDashCardWrp">
          <span style="font-size: 130%; font-weight: bold">Drive Team</span>
          <div style="display: flex; flex-direction: column; align-items: center">
            <b style="font-size: 400%; margin-top: 0.2em; margin-bottom: 0.2em; font-family: 'MontserratBold'"><?php echo $driveteam; ?></b>
            <span style="font-size: 100%; ">+1 this week</span>
          </div>
          
        </div>
        <div class="newDashCardWrp">
          <span style="font-size: 130%; font-weight: bold">Merchants</span>
          <div style="display: flex; flex-direction: column; align-items: center">
            <b style="font-size: 400%; margin-top: 0.2em; margin-bottom: 0.2em; font-family: 'MontserratBold'"><?php echo $merchantCount; ?></b>
            <span style="font-size: 100%; ">+1 this week</span>
          </div>
          
        </div>
        <div class="newDashCardWrp">
          <span style="font-size: 130%; font-weight: bold">Referrals</span>
          <div style="display: flex; flex-direction: column; align-items: center">
            <b style="font-size: 400%; margin-top: 0.2em; margin-bottom: 0.2em; font-family: 'MontserratBold'"><?php echo $referralsCount; ?></b>
            <span style="font-size: 100%; ">+1 this week</span>
          </div>
          
        </div>
      </div>
        <div id="leaderboardMainWrp" style="padding-left: 0">
           <div style="display: flex;width: 100%; justify-content: center">
              <span id="spanMainTitle1" style="">Leaderboard</span>
              
            </div>
            <div style="display: flex; flex-direction: column; width: 100%" >
             <div class="leaderboardSubHeader" style="margin-top:1em">
                <span style="width: 30%">Driver Name</span>
                <span>Drive team</span>
                <span>Merchants</span>
                <span>Deliveries</span>
                <span style="width: 11em; margin-right: 3em">Total referrals</span>   
              </div>
              <div class="subWrapper1" id="leaderboardSubWrp">
              <?php foreach($leaderboard as $m) { ?>
                <div>
                <div>
                 @if(!$m['profile_picture'] || $m['profile_picture']->src == '')
                                <img src="{{ url('images/user.jpeg')}}">

                                @else
                                <img src="{{ $m['profile_picture']->src }}" >
                                @endif <span> <?php echo $m['name']; ?> </span> </div>
                               
                <span><?php echo $m['driveteam']; ?></span>
                <span><?php echo $m['merchant']; ?></span>
                 <span><?php echo $m['deliveries']; ?></span>
                 <span style="width: 11em"><?php echo $m['referrals']; ?></span>
                </div>
              <?php  } ?>
               
            
              
            </div>



  </div>
</div>
      
      
    


  
</div>


</main>

@stop