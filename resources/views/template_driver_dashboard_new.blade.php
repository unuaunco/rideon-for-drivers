@include('common.head')

@if(@Auth::user()->user_type != "Affiliate")

@include('common.driver_dashboard_side_menu_new')

@else

@include('common.affiliate_driver_dashboard_side_menu_new')

@endif

@yield('main')




@include('common.foot')