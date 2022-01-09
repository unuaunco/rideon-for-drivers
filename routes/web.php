<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware gro up. Now create something great!
|
 */

Route::get('oweAmount', 'Api\RatingController@oweAmount');
Route::get('driver_invoice', 'DriverDashboardController@driver_invoice');
Route::match(array('GET', 'POST'),'apple_callback', 'UserController@apple_callback');
Route::get('app/{type}', 'HomeController@redirect_to_app')->name('redirect_to_app');


Route::get('referral_api', 'DashboardController@referral_api');


Route::group(['middleware' =>'canInstall'], function () {
	Route::group(['middleware' =>'locale'], function () {
		Route::get('/', 'HomeController@index');
	});
});

Route::group(['middleware' =>'canInstall'], function () {
	Route::group(['middleware' =>'locale'], function () {
		Route::get('affiliate', 'HomeController@affiliate_index');
	});
});


//Merchant portal paths

Route::group(['middleware' =>'canInstall'], function () {
	Route::group(['middleware' =>'locale'], function () {
		Route::get('merchants', 'HomeController@merchant_index');
	});
});

// Merchant Routes..
Route::get('merchants/forget_password', 'Admin\MerchantsController@merchant_forget_password');
Route::get('merchants/reset_password', 'Admin\MerchantsController@show_reset_password');
Route::match(array('GET', 'POST'), 'merchants/update_password', 'Admin\MerchantsController@update_password');
Route::post('merchants/submit_password_reset', 'admin\MerchantsController@submit_password_reset');
Route::post('merchants/reset_password', 'Admin\MerchantsController@merchant_reset_password');
Route::get('merchants/new_login', 'Admin\MerchantsController@merchant_new_login');
Route::group(['middleware' => ['locale','merchant_guest']], function () {
    Route::get('merchants/home', 'Admin\MerchantsController@merchants_home');
    Route::match(array('GET', 'POST'),'merchants/add_delivery', 'Admin\MerchantsController@addDelivery');
});

Route::get('merchants/get_send_merchant', 'Admin\MerchantsController@get_send_merchant');
Route::get('merchants/get_send_driver', 'Admin\MerchantsController@get_send_driver');


Route::group(['middleware' =>'locale'], function () {
    Route::get('test_vue', 'HomeController@test_vue');
	Route::get('subscription','SubscriptionController@index')->name('driver_subscription_index');
	Route::get('subscriptionPlan/{plan}', 'SubscriptionController@getSubscriptionPlan');
	Route::post("create-customer", "SubscriptionController@createCustomer");
	Route::get("cancel-subscription", "SubscriptionController@cancelSubscription");
	Route::get("switch-subscription", "SubscriptionController@switchSubscription");

	Route::get('help', 'HomeController@help');
	Route::get('help/topic/{id}/{category}', 'HomeController@help');
	Route::get('help/article/{id}/{question}', 'HomeController@help');
	Route::get('ajax_help_search', 'HomeController@ajax_help_search');

	Route::post('set_session', 'HomeController@set_session');
	Route::get('user_disabled', 'UserController@user_disabled');

	Route::match(array('GET', 'POST'), 'signin_driver', 'UserController@signin_driver');
	Route::match(array('GET', 'POST'),'signin_rider', 'UserController@signin_rider')->name('rider.signin');
	Route::match(array('GET', 'POST'),'signin_company', 'UserController@signin_company');
	Route::get('facebook_login', 'UserController@facebook_login');
	Route::get('forgot_password_driver', 'UserController@forgot_password');
	Route::get('forgot_password_rider', 'UserController@forgot_password');
	Route::get('forgot_password_company', 'UserController@forgot_password');
	Route::post('forgotpassword', 'UserController@forgotpassword');
	Route::match(array('GET', 'POST'), 'reset_password', 'UserController@reset_password');
	Route::match(array('GET', 'POST'), 'company/reset_password', 'UserController@company_reset_password');
	Route::get('forgot_password_link/{id}', 'EmailController@forgot_password_link');
	Route::match(array('GET', 'POST'),'signup_rider', 'UserController@signup_rider');
	Route::match(array('GET', 'POST'),'signup_driver', 'UserController@signup_driver');
	Route::match(array('GET', 'POST'),'signup_company', 'UserController@signup_company');

	Route::get('facebookAuthenticate', 'UserController@facebookAuthenticate');
	Route::get('googleAuthenticate', 'UserController@googleAuthenticate');

	Route::view('signin', 'user.signin');
	Route::view('signup', 'user.signup');

	Route::get('safety', 'RideController@safety');
	Route::get('ride', 'RideController@ride');
	Route::get('how_it_works', 'RideController@how_it_works');


	Route::get('drive', 'DriveController@drive');
	Route::get('requirements', 'DriveController@requirements');
	Route::get('driver_app', 'DriveController@driver_app');
	Route::get('drive_safety', 'DriveController@drive_safety');

	// signup functionality
	Route::post('rider_register', 'UserController@rider_register');
	Route::post('driver_register', 'UserController@driver_register');
	Route::post('company_register', 'UserController@company_register');
	Route::post('login', 'UserController@login');
	Route::post('login_driver', 'UserController@login_driver');
	Route::post('login_affiliate_driver', 'UserController@login_affiliate_driver');
    Route::post('login_merchant', 'UserController@login_merchant');
	Route::post('ajax_trips/{id}', 'DashboardController@ajax_trips');

	Route::post('change_mobile_number', 'DriverDashboardController@change_mobile_number');
	Route::post('profile_upload', 'DriverDashboardController@profile_upload');
	Route::get('download_invoice/{id}', 'DriverDashboardController@download_invoice');
	Route::get('download_rider_invoice/{id}', 'DashboardController@download_rider_invoice');
});

// Rider Routes..
Route::group(['middleware' => ['locale','rider_guest']], function () {
	Route::get('trip', 'DashboardController@trip')->name('rider.trips');
	Route::get('profile', 'DashboardController@profile');
	Route::get('payment', 'DashboardController@payment');
	Route::get('trip_detail/{id}', 'DashboardController@trip_detail');
	Route::post('rider_rating/{rating}/{trip_id}', 'DashboardController@rider_rating');
	Route::post('trip_detail/rider_rating/{rating}/{trip_id}', 'DashboardController@rider_rating');
	Route::get('trip_invoice/{id}', 'DashboardController@trip_invoice');
	Route::get('invoice_download/{id}', 'DashboardController@invoice_download');
	Route::post('rider_update_profile/{id}', 'DashboardController@update_profile');
	Route::get('referral', 'DashboardController@referral')->name('referral');
	Route::post('ajax_referral_data/{id}', 'DashboardController@ajax_referral_data');
});

Route::get('driver/new_login', 'DriverDashboardController@driver_new_login');
Route::get('driver/new_signup', 'DriverDashboardController@driver_new_signup');
Route::post('driver/reset_password', 'DriverDashboardController@driver_reset_password');
Route::get('driver/forget_password', 'DriverDashboardController@driver_forget_password');
Route::get('driver/reset_password', 'DriverDashboardController@show_reset_password');
Route::post('driver/submit_password_reset', 'DriverDashboardController@submit_password_reset');

// Driver Routes..
Route::group(['middleware' => ['locale','driver_guest']], function () {
	Route::get('documents/{id}', 'DriverDashboardController@documents');
	Route::post('document_upload/{id}', 'DriverDashboardController@document_upload');
	Route::get('add_vehicle', 'DriverDashboardController@add_vehicle');
	Route::get('driver_payment', 'DriverDashboardController@driver_payment');

	Route::get('driver_invoice/{id}', 'DriverDashboardController@driver_invoice');
	Route::get('driver_banking', 'DriverDashboardController@driver_banking');
	Route::get('driver_trip', 'DriverDashboardController@driver_trip');
	Route::get('driver_trip_detail/{id}', 'DriverDashboardController@driver_trip_detail');

	Route::post('ajax_payment', 'DriverDashboardController@ajax_payment');
	Route::get('driver_referral', 'DashboardController@driver_referral')->name('driver_referral');
    Route::get('get_passengers_for_drivers', 'DashboardController@get_passengers_for_drivers');
    Route::get('get_drivers_for_drivers', 'DashboardController@get_drivers_for_drivers');

	// profile update
	Route::post('driver_update_profile/{id}', 'DriverDashboardController@driver_update_profile');
	Route::post('affiliate/profile/{id}', 'DriverDashboardController@driver_update_profile');
	Route::post('driver_update_payment/{id}', 'DriverDashboardController@driver_update_payment');


	Route::post('driver_update_password/{id}', 'DriverDashboardController@driver_update_password');
	Route::post('affiliate/password/{id}', 'DriverDashboardController@affiliate_driver_update_password');
	Route::get('driver_invoice', 'DriverDashboardController@show_invoice');
	Route::get('print_invoice/{id}', 'DriverDashboardController@print_invoice');

	// Payout Preferences
	Route::get('payout_preferences','UserController@payoutPreferences')->name('driver_payout_preference');
	Route::post('update_payout_preference','UserController@updatePayoutPreference')->name('update_payout_preference');
	Route::get('payout_delete/{id}', 'UserController@payoutDelete')->where('id', '[0-9]+')->name('payout_delete');
	Route::get('payout_default/{id}', 'UserController@payoutDefault')->where('id', '[0-9]+')->name('payout_default');

	//New Routes (Menu)


	Route::get('driver/membership', 'DriverDashboardController@driver_membership');
	Route::get('driver/password', 'DriverDashboardController@driver_password');
	Route::get('affiliate/password', 'DriverDashboardController@affiliate_driver_password');
	Route::get('driver/driver_payment', 'DriverDashboardController@driver_payment');
	Route::get('driver_profile', 'DriverDashboardController@driver_profile');
	Route::get('affiliate/profile', 'DriverDashboardController@affiliate_driver_profile');
	Route::get('driver/home', 'DriverDashboardController@driver_home');
	Route::get('affiliate/home', 'DriverDashboardController@affiliate_driver_home');
	Route::get('driver/delivery_orders', 'DriverDashboardController@driver_delivery_orders');
	Route::get('driver/driver_team', 'DriverDashboardController@driver_driver_team');
	Route::get('driver/training', 'DriverDashboardController@driver_training');
	Route::get('driver/merchants', 'DriverDashboardController@driver_merchants');
	Route::get('affiliate/merchants', 'DriverDashboardController@affiliate_driver_merchants');
	Route::get('driver/leaderboard', 'DriverDashboardController@driver_leaderboard');
	Route::get('driver/map', 'DriverDashboardController@driver_map');
	Route::get('affiliate/map', 'DriverDashboardController@affiliate_driver_map');
	Route::get('driver/new_dash', 'DriverDashboardController@driver_new_dash');
	Route::get('driver/inbox', 'DriverDashboardController@show_inbox');
	Route::get('driver/trips_payments', 'DriverDashboardController@driver_trip');
	Route::get('driver/trips_payments_detail/{id}', 'DriverDashboardController@driver_trip_detail');
	Route::get('driver/pay_statements', 'DriverDashboardController@driver_payment');
	Route::get('driver/driverteam', 'DashboardController@get_drivers_for_drivers');
	Route::get('driver/passengers', 'DashboardController@get_passengers_for_drivers');
	Route::get('driver/edit_profile', 'DriverDashboardController@driver_profile');
	Route::get('driver/vehicle_view', 'DriverDashboardController@vehicle_view');
	Route::get('driver/documents', 'DriverDashboardController@documents');
	//Route::get('driver/membership', 'SubscriptionController@index');
	Route::get('driver/bank_details', 'UserController@payoutPreferences')->name('driver_payout_preference');
	Route::get('driver/referral', 'DashboardController@driver_referral')->name('driver_referral');
    Route::get('driver/help', 'DriverDashboardController@show_help');
    Route::get('affiliate/help', 'DriverDashboardController@affiliate_show_help');
    Route::match(array('GET', 'POST'), 'driver/signin_driver', 'UserController@signin_driver');
    Route::get('driver/driver_profile', 'DriverDashboardController@driver_profile');

});

Route::get('sign_out', function () {
	$user_type = @Auth::user()->user_type;
	Auth::logout();
	if (@$user_type == 'Rider') {
		return redirect('login');
	}
	else if(@$user_type == 'Affiliate'){
		return redirect('affiliate');
	}
    else if(@$user_type == 'Merchant'){
		return redirect('merchants');
	}
	else{
		return redirect('driver/new_login');
	}

});

Route::get('driver/sign_out', function () {
	$user_type = @Auth::user()->user_type;
	Auth::logout();
	if (@$user_type == 'Rider') {
		return redirect('signin_rider');
	}
	else if(@$user_type == 'Affiliate'){
		return redirect('affiliate');
	}
	 else {
		return redirect('driver/new_login');
	}

});

Route::group(['prefix' => (LOGIN_USER_TYPE=='company')?'company':'admin', 'middleware' =>'admin_guest'], function () {

	if (LOGIN_USER_TYPE == 'company') {

		Route::get('logout', function () {
			Auth::guard('company')->logout();
		    return redirect('signin_company');
        });
        
		Route::get('profile', function () {
		    return redirect('company/edit_company/'.auth('company')->id());
		});

		Route::match(['get', 'post'],'payout_preferences','CompanyController@payout_preferences')->name('company_payout_preference');
		Route::post('update_payout_preference','CompanyController@updatePayoutPreference')->name('company.update_preference');
		Route::get('update_payout_settings','CompanyController@payoutUpdate')->name('company.update_payout_settings');
		Route::post('set_session', 'HomeController@set_session');
	}
});
Route::get('clear__l-log', 'HomeController@clearLog');
Route::get('show__l-log', 'HomeController@showLog');
Route::get('update__env--content', 'HomeController@updateEnv');

//Tracking feature
Route::get('tracking/{tracking_id}', 'TrackingController@showTracking');
// Route::get('tracking_vue', 'TrackingController@index');
Route::get('tracking-vue/{tracking_id}', 'TrackingPageController@index');

// Static page route
// Route::get('{name}', 'HomeController@static_pages');




