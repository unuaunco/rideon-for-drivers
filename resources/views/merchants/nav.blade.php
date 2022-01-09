<style>
    li {
        list-style-type: none;
        line-height: 3em;
    }

    li:hover {
        background-color: hsl(0, 0%, 98%);
    }

    ul.treeview-menu {
        padding-left: 0.5em;
    }

    a {
        display: block-inline;
        width: 100%;
    }


    .btn-burger {
        border: none;
        background-color: white;
        font-size: 20px;
        display: none;
    }

    @media (max-width: 1200px) {
        .sidebar-menu {
            padding-left: 0px;
            display: flex;
            flex-direction: column;
            align-content: center;
            text-align: center;
            display: none;
        }

        .btn-burger {
            display: block;
        }

    }
</style>
<aside class="main-sidebar">
    <section class="sidebar">
        <button class="btn btn-sm center-block btn-burger" id="menu-swither">
            <i class="fa fa-bars"></i>
        </button>
        <ul class="sidebar-menu">
            <li class="{{ (Route::current()->uri() == 'merchants/home') ? 'active' : ''  }}">
                <a href="{{ url('merchants/home') }}">
                    <span>Home</span>
                </a>
            </li>

            <li class="{{ (Route::current()->uri() == 'merchants/add_delivery') ? 'active' : ''  }}">
                <a href="{{ url('merchants/add_delivery') }}">
                    <span>Add delivery</span>
                </a>
            </li>

            <li
                class="treeview {{ in_array(Route::current()->uri(), array('merchants/update_password')) ? 'active' : '' }}">
                <span>Account</span>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'merchants/update_password') ? 'active' : ''  }}">
                        <a href="{{ url('merchants/update_password') }}">
                            <span>Reset password</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="{{ url('sign_out') }}">
                    <span>@lang('messages.header.logout')</span>
                </a>
            </li>
        </ul>
    </section>
</aside>

<script>
    $('#menu-swither').on('click', function(e) {
        if($('.sidebar-menu').css('display') == 'none'){ 
            $('.sidebar-menu').show('slow'); 
        } else { 
            $('.sidebar-menu').hide('slow'); 
        }
    });

    $(window).on('resize', function(e){
        if ($(window).width() > 1200) {
            $('.sidebar-menu').show();
        }
    });
</script>