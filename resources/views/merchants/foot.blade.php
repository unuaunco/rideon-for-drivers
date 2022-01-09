  <div class="alert cookie-alert alert-dismissible" style="display:none">
    <a href="#" class="close close_cookie-alert" data-dismiss="alert" aria-label="close">&times;</a>
    <p>
      {{trans('messages.footer.using_cookies',['site_name'=>$site_name])}} <a href="{{url('privacy_policy')}}">{{trans('messages.user.privacy')}}.</a>
    </p>
  </div>

  {!! Html::script('js/angular.js') !!}
  {!! Html::script('js/angular-sanitize.js') !!}
  <script>
    var app = angular.module('App', ['ngSanitize']);
    var APP_URL = {!! json_encode(url('/')) !!};
    var LOGIN_USER_TYPE = '{!! LOGIN_USER_TYPE !!}';
  </script>

  {!! Html::script('js/common.js?v='.$version) !!}
  {!! Html::script('js/user.js?v='.$version) !!}
  {!! Html::script('js/main.js?v='.$version) !!}
  {!! Html::script('js/bootstrap.min.js') !!}
  {!! Html::script('js/jquery.bxslider.min.js') !!}
  {!! Html::script('js/jquery.sliderTabs.min.js') !!}
  {!! Html::script('js/responsiveslides.js?v='.$version) !!}

  {!! $head_code !!}

  <!-- Start Display Cookie Alert in foot -->
  <script type="text/javascript">
    $(document).on('click','.cookie-alert .close_cookie-alert',function() {
        writeCookie('status','1',10);
      })

    var getCookiebyName = function(){
      var pair = document.cookie.match(new RegExp('status' + '=([^;]+)'));
      var result = pair ? pair[1] : 0;
      $('.cookie-alert').show();
      if(result) {
        $('.cookie-alert').hide();
        return false;
      }
    };

    var url = window.location.href;
    var arr = url.split("/");
    var result = arr[0] + "//" + arr[2];
    var domain =  result.replace(/(^\w+:|^)\/\//, '');

    writeCookie = function(cname, cvalue, days) {
      var dt, expires;
      dt = new Date();
      dt.setTime(dt.getTime()+(days*24*60*60*1000));
      expires = "; expires="+dt.toGMTString();
      document.cookie = cname+"="+cvalue+expires+'; domain='+domain;
    }

    getCookiebyName();

</script>
<!-- End Display Cookie Alert in foot -->

@if (Route::current()->uri() == 'tracking/{tracking_id}' || Route::current()->uri() == 'merchants/home' || Route::current()->uri() == 'merchants/add_delivery')
    <script src="{{ url('admin_assets/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ url('admin_assets/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ url('admin_assets/plugins/datepicker/bootstrap-datepicker.js') }}"></script>
    {{-- <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key={{$map_key}}&sensor=false&libraries=places"></script> --}}
    <script src="{{ url('admin_assets/dist/js/tracking.js') }}"></script>
    <script src="{{ url('admin_assets/dist/js/moment.min.js') }}"></script>
    <script src="{{ url('admin_assets/dist/js/moment_timezone.js') }}"></script>
    <script src="{{ url('admin_assets/dist/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ url('admin_assets/dist/js/jquery.datetimepicker.full.min.js') }}"></script>
    <script src="{{ url('js/selectize.js') }}"></script>
    <script src="{{ url('admin_assets/plugins/jQuery/jquery.validate.js') }}"></script>
@endif

@stack('scripts')