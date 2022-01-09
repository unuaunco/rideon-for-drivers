<aside class="main-sidebar">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left image">
                @php
                if(LOGIN_USER_TYPE=='company'){
                $user = Auth::guard('company')->user();
                $company_user = true;
                $first_segment = 'company';
                }
                else{
                $user = Auth::guard('admin')->user();
                $company_user = false;
                $first_segment = 'admin';
                }
                @endphp
                @if(!$company_user || $user->profile ==null)
                <img src="{{ url('admin_assets/dist/img/avatar04.png') }}" class="img-circle" alt="User Image">
                @else
                <img src="{{ $user->profile }}" class="img-circle" alt="User Image">
                @endif
            </div>
            <div class="pull-left info">
                <p>{{ (!$company_user)?$user->username:$user->name }}</p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="header">MAIN NAVIGATION</li>
            {{-- <li class="{{ (Route::current()->uri() == $first_segment.'/dashboard') ? 'active' : ''  }}"><a
                href="{{ url($first_segment.'/dashboard') }}"><i class="fa fa-dashboard"></i><span>Dashboard</span></a>
            </li> --}}
            @if(@$user->can('view_delivery'))
            <li class="{{ (Route::current()->uri() == 'admin/home_delivery_dashboard') ? 'active' : ''  }}">
                <a href="{{ url('admin/home_delivery_dashboard') }}">
                    <i class="fa fa-tachometer"></i>
                    <span>Deliveries Dashboard</span>
                </a>
            </li>
            @endif
            {{-- @if(@$user->can('manage_chat'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/chat') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/chat') }}" target="_blank"><i
                        class="fa fa-comments-o"></i><span>SendBird Chat</span></a></li>
            @endif --}}
            @if(@$user->can('manage_application'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/application_driver' || Route::current()->uri() == 'admin/application_merchant') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-user-plus"></i>
                    <span>Applications Management</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'admin/application_driver') ? 'active' : ''  }}">
                        <a href="{{ url('admin/application_driver') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Drivers</span>
                        </a>
                    </li>
                    <li class="{{ (Route::current()->uri() == 'admin/application_merchant') ? 'active' : ''  }}">
                        <a href="{{ url('admin/application_merchant') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Merchants</span>
                        </a>
                    </li>
                    <li class="{{ (Route::current()->uri() == 'admin/application_affiliate') ? 'active' : ''  }}">
                        <a href="{{ url('admin/application_affiliate') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Affiliate</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            @if(@$user->can('view_delivery'))
            <li class="treeview {{ in_array(Route::current()->uri(), array('admin/home_delivery', 'admin/merchants', 'admin/shifts')) ? 'active' : '' }}">
                <a href="#">
                    <i class="fa fa-gift"></i>
                    <span>Manage Delivery Orders</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'admin/home_delivery') ? 'active' : ''  }}">
                        <a href="{{ url('admin/home_delivery') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    @if(@$user->can('view_merchant'))
                    <li class="{{ (Route::current()->uri() == 'admin/merchants') ? 'active' : ''  }}">
                        <a href="{{ url('admin/merchants') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Merchants</span>
                        </a>
                    </li>
                    @endif
                    @if(@$user->can('view_shift'))
                    <li class="{{ (Route::current()->uri() == 'admin/shifts') ? 'active' : ''  }}">
                        <a href="{{ url('admin/shifts') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Shifts</span>
                        </a>
                    </li>
                    @endif
                    <li class="{{ (Route::current()->uri() == 'admin/home_delivery_dashboard') ? 'active' : ''  }}">
                        <a href="{{ url('admin/home_delivery_dashboard') }}">
                            <i class="fa fa-tachometer"></i>
                            <span>Deliveries Dashboard</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
            @if($company_user || @$user->can('view_driver'))
                <li class="{{ (Route::current()->uri() == $first_segment.'/driver') ? 'active' : ''  }}">
                    <a href="{{ url($first_segment.'/driver') }}">
                        <i class="fa fa-drivers-license-o"></i>
                        <span>Manage Drivers</span>
                    </a>
                </li>
            @endif
            @if($company_user || @$user->can('view_community_leader'))
                <li class="{{ (Route::current()->uri() == $first_segment.'/community_leader') ? 'active' : ''  }}">
                    <a href="{{ url($first_segment.'/community_leader') }}">
                        <i class="fa fa-users"></i>
                        <span>Manage Community Leaders</span>
                    </a>
                </li>
            @endif
            @if($company_user || @$user->can('view_affiliate'))
                <li class="{{ (Route::current()->uri() == $first_segment.'/affiliate') ? 'active' : ''  }}">
                    <a href="{{ url($first_segment.'/affiliate') }}">
                        <i class="fa fa-users"></i>
                        <span>Manage Affiliates</span>
                    </a>
                </li>
            @endif
            @if(@$user->can('view_rider'))
                <li class="{{ (Route::current()->uri() == 'admin/rider') ? 'active' : ''  }}">
                    <a href="{{ url('admin/rider') }}">
                        <i class="fa fa-user-o"></i>
                        <span>Manage Riders</span>
                    </a>
                </li>
            @endif
            @if($company_user || @$user->can('manage_map') || @$user->can('manage_heat_map'))
            <li
                class="treeview {{ (Route::current()->uri() == $first_segment.'/map' || Route::current()->uri() == $first_segment.'/heat-map') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-map-marker" aria-hidden="true"></i> <span>Manage Map</span> <i
                        class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == $first_segment.'/map') ? 'active' : ''  }}"><a
                            href="{{ url($first_segment.'/map') }}"><i class="fa fa-circle-o"></i><span>Map
                                View</span></a></li>
                    <li class="{{ (Route::current()->uri() == $first_segment.'/heat-map') ? 'active' : ''  }}"><a
                            href="{{ url($first_segment.'/heat-map') }}"><i
                                class="fa fa-circle-o"></i><span>HeatMap</span></a></li>
                </ul>
            </li>
            @endif
            {{-- @if($company_user || @$user->can('manage_driver_payments') || @$user->can('manage_company_payments'))
			<li class="treeview {{ (Route::current()->uri() == 'admin/payout/overall' || Route::current()->uri() == 'admin/payout/company/overall' || Route::current()->uri() == 'company/payout/overall') ? 'active' : ''  }}">
            <a href="#">
                <i class="fa fa-dollar" aria-hidden="true"></i> <span>Manage Payouts</span> <i
                    class="fa fa-angle-left pull-right"></i>
            </a>
            <ul class="treeview-menu">
                @if(@$user->can('manage_company_payment'))
                <li class="{{ (Route::current()->uri() == 'admin/payout/company/overall') ? 'active' : ''  }}"><a
                        href="{{ url('admin/payout/company/overall') }}"><i class="fa fa-circle-o"></i><span>Company
                            Payouts</span></a></li>
                @endif
                @if($company_user || @$user->can('manage_driver_payments'))
                <li class="{{ (Route::current()->uri() == $first_segment.'/payout/overall') ? 'active' : ''  }}"><a
                        href="{{ url($first_segment.'/payout/overall') }}"><i class="fa fa-circle-o"></i><span>Driver
                            Payouts</span></a></li>
                @endif
            </ul>
            </li>
            @endif --}}
            @if(@$user->can('manage_rider_referrals') || @$user->can('manage_driver_referrals'))
            <li
                class="treeview {{ 
                    in_array(Route::current()->uri(), array('admin/referrals/rider', 'admin/referrals/driver', 'admin/referrals/community_leader', 'admin/referral_settings' )) ? 'active' : ''
                }}">
                <a href="#">
                    <i class="fa fa-users"></i>
                    <span>Referrals</span><i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    @if(@$user->can('manage_rider_referrals'))
                    <li class="{{ (Route::current()->uri() == 'admin/referrals/rider') ? 'active' : ''  }}">
                        <a href="{{ url('admin/referrals/rider') }}"><i class="fa fa-circle-o"></i>
                            <span> Riders </span>
                        </a>
                    </li>
                    @endif
                    @if(@$user->can('manage_driver_referrals'))
                    <li class="{{ (Route::current()->uri() == 'admin/referrals/driver') ? 'active' : ''  }}">
                        <a href="{{ url('admin/referrals/driver') }}"><i class="fa fa-circle-o"></i>
                            <span> Drivers </span>
                        </a>
                    </li>
                    @endif
                    @if(@$user->can('manage_driver_referrals'))
                    <li class="{{ (Route::current()->uri() == 'admin/referrals/community_leader') ? 'active' : ''  }}">
                        <a href="{{ url('admin/referrals/community_leader') }}"><i class="fa fa-circle-o"></i>
                            <span> Community Leaders </span>
                        </a>
                    </li>
                    @endif
                    @if(@$user->can('manage_referral_settings'))
                        <li class="{{ (Route::current()->uri() == 'admin/referral_settings') ? 'active' : ''  }}">
                            <a href="{{ url('admin/referral_settings') }}">
                                <i class="fa fa-circle-o"></i>
                                <span>Manage Referral Settings</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
            @endif
            @if(@$user->can('view_subscriptions'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/subscriptions/driver' || Route::current()->uri() == 'admin/subscriptions/plan') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-handshake-o"></i> <span>Manage Subscriptions</span> <i
                        class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'admin/subscriptions/driver') ? 'active' : ''  }}"><a
                            href="{{ url('admin/subscriptions/driver') }}"><i
                                class="fa fa-circle-o"></i><span>Subscribed Drivers</span></a></li>
                    <li class="{{ (Route::current()->uri() == 'admin/subscriptions/plan') ? 'active' : ''  }}"><a
                            href="{{ url('admin/subscriptions/plan') }}"><i
                                class="fa fa-circle-o"></i><span>Subscription Plans</span></a></li>
                </ul>
            </li>
            @endif
            @if(@$user->can('view_transactions'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/payout_transactions' || Route::current()->uri() == 'admin/invoice_transactions') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-money"></i> <span>Manage Transactions</span> <i
                        class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'admin/payout_statistics') ? 'active' : ''  }}"><a
                            href="{{ url('admin/payout_statistics') }}"><i class="fa fa-line-chart"></i><span>Payout
                                statistics</span></a></li>
                    <li class="{{ (Route::current()->uri() == 'admin/payout_transactions') ? 'active' : ''  }}"><a
                            href="{{ url('admin/payout_transactions') }}"><i class="fa fa-circle-o"></i><span>Payments
                                to drivers</span></a></li>
                    <li class="{{ (Route::current()->uri() == 'admin/invoice_transactions') ? 'active' : ''  }}"><a
                            href="{{ url('admin/invoice_transactions') }}"><i class="fa fa-circle-o"></i><span>Invoices
                                to merchants</span></a></li>
                </ul>
            </li>
            @endif
            @if(@$user->can('manage_admin'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/admin_user' || Route::current()->uri() == 'admin/roles') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-id-badge"></i> <span>Manage Admin</span> <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'admin/admin_user') ? 'active' : ''  }}"><a
                            href="{{ url('admin/admin_user') }}"><i class="fa fa-circle-o"></i><span>Admin
                                Users</span></a></li>
                    <li class="{{ (Route::current()->uri() == 'admin/roles') ? 'active' : ''  }}"><a
                            href="{{ url('admin/roles') }}"><i class="fa fa-circle-o"></i><span>Roles &
                                Permissions</span></a></li>
                </ul>
            </li>
            @endif
            @if($company_user && $user->id != 1)
            <li class="{{ (Route::current()->uri() == $first_segment.'/payout_preferences') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/payout_preferences') }}"><i class="fa fa-paypal"></i><span>Payout
                        Preferences</span></a></li>
            @endif

            @if($company_user || @$user->can('manage_send_message'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/send_message') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/send_message') }}"><i class="fa fa-bullhorn"></i><span>Send
                        Messages</span></a></li>
            @endif
            @if(@$user->can('manage_email_settings') || @$user->can('manage_send_email'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/email_settings' || Route::current()->uri() == 'admin/send_email') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-envelope-o"></i>
                    <span>Manage Emails</span><i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    @if(@$user->can('manage_send_email'))
                    <li class="{{ (Route::current()->uri() == 'admin/send_email') ? 'active' : ''  }}">
                        <a href="{{ url('admin/send_email') }}"><i class="fa fa-circle-o"></i>
                            <span>Send Email</span>
                        </a>
                    </li>
                    @endif
                    @if(@$user->can('manage_email_settings'))
                    <li class="{{ (Route::current()->uri() == 'admin/email_settings') ? 'active' : ''  }}">
                        <a href="{{ url('admin/email_settings') }}"><i class="fa fa-circle-o"></i>
                            <span>Email Settings</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif
            {{-- @if(@$user->can('view_company'))
            <li class="{{ (Route::current()->uri() == 'admin/company') ? 'active' : ''  }}"><a
                    href="{{ url('admin/company') }}"><i class="fa fa-building"></i><span>Manage Company</span></a></li>
            @endif --}}
            @if(@$user->can('manage_vehicle') || @$user->can('manage_vehicle_type'))
                <li
                    class="treeview {{ 
                        in_array(Route::current()->uri(), array('admin/vehicle_type', 'admin/vehicle' )) ? 'active' : ''
                    }}">
                    <a href="#">
                        <i class="fa fa-car"></i>
                        <span>Manage Vehicles</span><i class="fa fa-angle-left pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        @if($company_user || @$user->can('manage_vehicle'))
                            <li class="{{ (Route::current()->uri() == $first_segment.'/vehicle') ? 'active' : ''  }}">
                                <a href="{{ url($first_segment.'/vehicle') }}">
                                    <i class="fa fa-circle-o"></i>
                                    <span>Manage Drivers' Vehicles</span>
                                </a>
                            </li>
                        @endif
                        @if(@$user->can('manage_vehicle_type'))
                            <li class="{{ (Route::current()->uri() == 'admin/vehicle_type') ? 'active' : ''  }}">
                                <a href="{{ url('admin/vehicle_type') }}">
                                    <i class="fa fa-circle-o"></i>
                                    <span>Manage Vehicles Type</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if(@$user->can('manage_locations'))
            <li class="{{ (Route::current()->uri() == 'admin/locations') ? 'active' : ''  }}"><a
                    href="{{ url('admin/locations') }}"><i class="fa fa-map-o"></i><span>Manage Locations</span></a>
            </li>
            @endif
            {{-- @if(($company_user && @$user->status == 'Active') || @$user->can('manage_manual_booking'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/manual_booking/{id?}') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/manual_booking') }}"><i class="fa fa-address-book"
                        aria-hidden="true"></i><span>Manual Booking</span></a></li>
            @endif
            @if($company_user || @$user->can('manage_manual_booking'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/later_booking') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/later_booking') }}"><i class="fa fa-list-alt"></i><span>View Manual
                        Booking</span></a></li>
            @endif --}}
            @if(@$user->can('manage_peak_based_fare'))
            <li class="{{ (Route::current()->uri() == 'admin/manage_fare') ? 'active' : ''  }}"><a
                    href="{{ url('admin/manage_fare') }}"><i class="fa fa fa-dollar"></i><span>Manage Fare</span></a>
            </li>
            @endif
            @if(@$user->can('view_additional_reason'))
            <li class="{{ (Route::current()->uri() == 'admin/additional-reasons') ? 'active' : ''  }}"><a
                    href="{{ url('admin/additional-reasons') }}"><i class="fa fa fa-dollar"></i><span> Additional
                        Reasons</span></a></li>
            @endif
            @if($company_user || @$user->can('manage_trips'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/trips') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/trips') }}"><i class="fa fa-taxi"></i><span> Manage Trips</span></a>
            </li>
            @endif
            @if($company_user || @$user->can('manage_statements'))
            <li
                class="treeview {{ (Route::current()->uri() == $first_segment.'/statements/{type}') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-area-chart"></i> <span>Manage Statements</span> <i
                        class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li
                        class="{{ (Route::current()->uri() == $first_segment.'/statements/overall') ? 'active' : ''  }}">
                        <a href="{{ url($first_segment.'/statements/overall') }}"><i
                                class="fa fa-circle-o"></i><span>Overall Ride Statments</span></a></li>
                    <li class="{{ (Route::current()->uri() == $first_segment.'/statements/driver') ? 'active' : ''  }}">
                        <a href="{{ url($first_segment.'/statements/driver') }}"><i
                                class="fa fa-circle-o"></i><span>Driver Statement</span></a></li>
                </ul>
            </li>
            @endif
            @if(@$user->can('manage_wallet') || @$user->can('manage_promo_code'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/wallet/{user_type}' || Route::current()->uri() == 'admin/promo_code') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-google-wallet"></i> <span>Manage Wallet & Promo</span> <i
                        class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    @if($company_user || @$user->can('manage_wallet'))
                    <li class="treeview {{ (@$navigation == 'manage_wallet') ? 'active' : ''  }}">
                        <a href="{{ route('wallet',['user_type' => 'Rider']) }}"><i class="fa fa-circle-o"></i>
                            <span> Manage Wallet Amount </span>
                        </a>
                    </li>
                    @endif
                    @if(@$user->can('manage_promo_code'))
                    <li class="{{ (Route::current()->uri() == 'admin/promo_code') ? 'active' : ''  }}"><a
                            href="{{ url('admin/promo_code') }}"><i class="fa fa-circle-o"></i><span>Manage Promo
                                Code</span></a></li>
                    @endif
                </ul>
            </li>
            @endif
            @if($company_user || @$user->can('manage_cancel_trips'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/cancel_trips') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/cancel_trips') }}"><i class="fa fa-chain-broken"></i><span>Manage
                        Canceled Trips</span></a></li>
            @endif
            @if($company_user || @$user->can('manage_owe_amount'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/owe') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/owe') }}"><i class="fa fa-money"></i><span>Manage Owe
                        Amount</span></a></li>
            @endif
            @if($company_user || @$user->can('manage_requests'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/request') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/request') }}"><i class="fa fa-paper-plane-o"></i><span>Manage Ride
                        Requests</span></a></li>
            @endif
            @if($company_user || @$user->can('manage_payments'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/payments') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/payments') }}"><i class="fa fa-usd"></i><span>Manage
                        Payments</span></a></li>
            @endif
            @if($company_user || @$user->can('manage_rating'))
            <li class="{{ (Route::current()->uri() == $first_segment.'/rating') ? 'active' : ''  }}"><a
                    href="{{ url($first_segment.'/rating') }}"><i class="fa fa-star"></i><span>Ratings</span></a></li>
            @endif
            {{-- @if(@$user->can('manage_help'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/help' || Route::current()->uri() == 'admin/help_category' || Route::current()->uri() == 'admin/help_subcategory') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-support"></i> <span>Manage Help</span> <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'admin/help') ? 'active' : ''  }}"><a
                            href="{{ url('admin/help') }}"><i class="fa fa-circle-o"></i><span>Help</span></a></li>
                    <li class="{{ (Route::current()->uri() == 'admin/help_category') ? 'active' : ''  }}"><a
                            href="{{ url('admin/help_category') }}"><i
                                class="fa fa-circle-o"></i><span>Category</span></a></li>
                    <li class="{{ (Route::current()->uri() == 'admin/help_subcategory') ? 'active' : ''  }}"><a
                            href="{{ url('admin/help_subcategory') }}"><i
                                class="fa fa-circle-o"></i><span>Subcategory</span></a></li>
                </ul>
            </li>
            @endif --}}
            @if(@$user->can('manage_fees'))
            <li class="{{ (Route::current()->uri() == 'admin/fees') ? 'active' : ''  }}"><a
                    href="{{ url('admin/fees') }}"><i class="fa fa-dollar"></i><span>Manage Fees</span></a></li>
            @endif
            {{-- @if(@$user->can('manage_referral_settings'))
            <li class="{{ (Route::current()->uri() == 'admin/referral_settings') ? 'active' : ''  }}"><a
                    href="{{ url('admin/referral_settings') }}"><i class="fa fa-users"></i><span>Manage Referral Settings</span></a></li>
            @endif --}}
            {{-- @if(@$user->can('manage_metas'))
            <li class="{{ (Route::current()->uri() == 'admin/metas') ? 'active' : ''  }}"><a
                    href="{{ url('admin/metas') }}"><i class="fa fa-bar-chart"></i><span>Manage Metas</span></a></li>
            @endif --}}
            @if(@$user->can('view_manage_reason'))
            <li class="{{ (Route::current()->uri() == 'admin/cancel-reason') ? 'active' : ''  }}"><a
                    href="{{ url('admin/cancel-reason') }}"><i class="fa fa-bar-chart"></i><span>Manage Cancel
                        Reason</span></a></li>
            @endif
            {{-- @if(@$user->can('manage_static_pages'))
            <li class="{{ (Route::current()->uri() == 'admin/pages') ? 'active' : ''  }}"><a
                    href="{{ url('admin/pages') }}"><i class="fa fa-newspaper-o"></i><span>Manage Static
                        Pages</span></a></li>
            @endif --}}
            @if(@$user->can('manage_currency'))
            <li class="{{ (Route::current()->uri() == 'admin/currency') ? 'active' : ''  }}"><a
                    href="{{ url('admin/currency') }}"><i class="fa fa-eur"></i><span>Manage Currency</span></a></li>
            @endif
            {{-- @if(@$user->can('manage_language'))
            <li class="{{ (Route::current()->uri() == 'admin/language') ? 'active' : ''  }}"><a
                    href="{{ url('admin/language') }}"><i class="fa fa-language"></i><span>Manage Language</span></a>
            </li>
            @endif --}}
            {{-- @if(@$user->can('manage_country'))
            <li class="{{ (Route::current()->uri() == 'admin/country') ? 'active' : ''  }}"><a
                    href="{{ url('admin/country') }}"><i class="fa fa-globe"></i><span>Manage Country</span></a></li>
            @endif --}}
            {{-- @if(@$user->can('manage_api_credentials'))
            <li class="{{ (Route::current()->uri() == 'admin/api_credentials') ? 'active' : ''  }}"><a
                    href="{{ url('admin/api_credentials') }}"><i class="fa fa-gear"></i><span>Api Credentials</span></a>
            </li>
            @endif
            @if(@$user->can('manage_payment_gateway'))
            <li class="{{ (Route::current()->uri() == 'admin/payment_gateway') ? 'active' : ''  }}"><a
                    href="{{ url('admin/payment_gateway') }}"><i class="fa fa-paypal"></i><span>Payment Gateway</span></a></li>
            @endif --}}
            {{-- @if(@$user->can('manage_join_us'))
            <li class="{{ (Route::current()->uri() == 'admin/join_us') ? 'active' : ''  }}"><a
                    href="{{ url('admin/join_us') }}"><i class="fa fa-share-alt"></i><span>Join Us Links</span></a></li>
            @endif --}}
            {{-- @if(@$user->can('manage_site_settings'))
            <li class="{{ (Route::current()->uri() == 'admin/site_setting') ? 'active' : ''  }}"><a
                    href="{{ url('admin/site_setting') }}"><i class="fa fa-cogs"></i><span>Site Setting</span></a></li>
            @endif --}}
            @if(@$user->can('manage_site_settings'))
                <li
                    class="treeview {{
                        in_array(Route::current()->uri(), array('admin/site_setting', 'admin/payment_gateway', 'admin/api_credentials', 'admin/country' )) ? 'active' : ''  
                    }}">
                    <a href="#">
                        <i class="fa fa-cogs"></i>
                        <span>Settings</span>
                        <i class="fa fa-angle-left pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <li class="{{ (Route::current()->uri() == 'admin/site_setting') ? 'active' : ''  }}">
                            <a href="{{ url('admin/site_setting') }}">
                                <i class="fa fa-circle-o"></i>
                                <span>Site Setting</span>
                            </a>
                        </li>
                        <li class="{{ (Route::current()->uri() == 'admin/payment_gateway') ? 'active' : ''  }}">
                            <a href="{{ url('admin/payment_gateway') }}">
                                <i class="fa fa-circle-o"></i>
                                <span>Payment Gateway</span>
                            </a>
                        </li>
                        <li class="{{ (Route::current()->uri() == 'admin/api_credentials') ? 'active' : ''  }}">
                            <a href="{{ url('admin/api_credentials') }}">
                                <i class="fa fa-circle-o"></i>
                                <span>Api Credentials</span>
                            </a>
                        </li>
                        <li class="{{ (Route::current()->uri() == 'admin/country') ? 'active' : ''  }}">
                            <a href="{{ url('admin/country') }}">
                                <i class="fa fa-circle-o"></i>
                                <span>Manage Country</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
            @if(@$user->can('manage_site_settings'))
            <li
                class="treeview {{ (Route::current()->uri() == 'admin/import_drivers' || Route::current()->uri() == 'admin/import_leaders' || Route::current()->uri() == 'admin/import_merchants') ? 'active' : ''  }}">
                <a href="#">
                    <i class="fa fa-cogs"></i>
                    <span>Import users</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="{{ (Route::current()->uri() == 'admin/import_leaders') ? 'active' : ''  }}">
                        <a href="{{ url('admin/import_leaders') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Community Leaders</span>
                        </a>
                    </li>
                    <li class="{{ (Route::current()->uri() == 'admin/import_drivers') ? 'active' : ''  }}">
                        <a href="{{ url('admin/import_drivers') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Drivers</span>
                        </a>
                    </li>
                    <li class="{{ (Route::current()->uri() == 'admin/import_merchants') ? 'active' : ''  }}">
                        <a href="{{ url('admin/import_merchants') }}">
                            <i class="fa fa-circle-o"></i>
                            <span>Merchants</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
        </ul>
    </section>
</aside>