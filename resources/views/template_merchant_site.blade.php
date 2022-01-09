@include('merchants.head')

<div class="contaner-fluid">
    <div class="row">
        <div class="col-lg-12">
            @include('merchants.header')
        </div>
    </div>
    <div class="row">
        <div class="col-lg-2">
            @include('merchants.nav')
        </div>
        <div class="col-lg-10">
            @yield('main')
        </div>
    </div>
</div>



@include('merchants.foot')