<title>Edit Profile</title>
@extends('template_driver_dashboard_new') 
@section('main')
<div class="col-lg-9 col-md-9 col-sm-12 col-xs-12 flexbox__item four-fifths page-content" style="padding:0px !important;" ng-controller="facebook_account_kit">
  @include('common.affiliate_driver_dashboard_header_new')
  <div id="leaderboardMainWrp">
           <div style="display: flex;width: 100%">
              <span id="spanMainTitle1" style="">Leaderboard</span>
              
            </div>
            <div style="display: flex; flex-direction: column; width: 100%" >
             <div class="leaderboardSubHeader">
                <span style="width: 30%">Driver Name</span>
                <span>Phone</span>
                <span>Location</span>
                <span>Since</span>
                <span style="width: 7em">Status</span>   
              </div>
              <div class="subWrapper1" id="leaderboardSubWrp">
              <?php foreach($merchants as $m) { ?>
                <div>
                <div>
                 @if($m->profile_picture->src == '')
                                <img src="{{ url('images/user.jpeg')}}">

                                @else
                                <img src="{{ $m->profile_picture->src }}" >
                                @endif <span> <?php echo $m['first_name'] . " " . $m['last_name']; ?> </span> </div>
                                <span><?php echo $m['mobile_number']; ?></span>
                <span><?php echo $m['address']; ?></span>
                <span><?php echo $m['since']; ?></span>
                 <span class="status{{$m->status}}1 status1"><?php echo str_replace("_", " ", $m->status); ?></span>
                </div>
              <?php  } ?>
               
            
              
            </div>



  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.0/jquery.min.js"></script>

</main>
@stop