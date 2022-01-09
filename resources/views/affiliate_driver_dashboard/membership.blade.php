<title>Membership</title>
@extends('template_driver_dashboard_new') 
@section('main')
<div class="col-lg-9 col-md-9 col-sm-12 col-xs-12 flexbox__item four-fifths page-content" style="padding:0px !important;" ng-controller="facebook_account_kit">
  @include('common.affiliate_driver_dashboard_header_new')
  <div style="height: 100%; width: 100%; display: flex; padding-left:1.5rem;" id="profileWrp" class="mainWrp1">

    <div style="display: flex; width: 100%; justify-content: space-between;margin-top: 1em;">
      <div style=" display: flex; flex-direction: column;padding: 15px 10px;margin-right: 2.5em">
        <span style="font-size: 140%; color: #1B187F;font-weight: bold; margin-bottom: 0.4em; font-family:'MontserratReg'">Membership</span>
        <div style="display: flex; flex-direction: row; align-items: center ">
          <span style="font-size: 90%; font-weight: bold"><?php echo $sub_name; ?></span>
          <span style="font-size: 85%; margin-left: 1.5em; color: #8f2a06; font-weight: bold">Cancel</span>
        </div>
     </div>
    </div>

  </div>
</div>
</div>
</div>
</div>
</main>
@stop


<script>
  $(function() {
    $("#profileLeftWrp span").click(function() {
       $("#profileRightWrp > div.current").removeClass("current");
       $("#profileRightWrp > div[data-tab='" + $(this).data("tab") + "']").addClass("current");
      $("#profileLeftWrp span.current").removeClass("current");
      $(this).addClass("current");

    })
  });
</script>