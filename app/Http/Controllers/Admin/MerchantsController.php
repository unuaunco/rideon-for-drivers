<?php

namespace App\Http\Controllers\Admin;

use App;
use App\DataTables\MerchantsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailController;
use App\Http\Helper\RequestHelper;
use App\Http\Start\Helpers;
use App\Models\Country;
use App\Models\DriverAddress;
use App\Models\DriverLocation;
use App\Models\HomeDeliveryOrder;
use App\Models\Merchant;
use App\Models\MerchantIntegrationType;
use App\Models\PasswordResets;
use App\Models\ReferralUser;
use App\Models\Request as RideRequest;
use App\Models\User;
use Auth;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;
use Hash;
use Session;
use \Carbon\Carbon;

class MerchantsController extends Controller
{
    protected $helper; // Global variable for instance of Helpers

    public function __construct(RequestHelper $request)
    {
        $this->helper = new Helpers;
        $this->otp_helper = resolve('App\Http\Helper\OtpHelper');
        $this->map_service = resolve('App\Services\MapServices\HereMapService');
        $this->request_helper = $request;
    }

    /**
     * Load Datatable for Merchants
     *
     * @param array $dataTable  Instance of Merchants DataTable
     * @return datatable
     */
    public function index(MerchantsDataTable $dataTable)
    {
        return $dataTable->render('admin.merchant.view');
    }

    /**
     * Merchants tracking page
     *
     * @param array $dataTable  Instance of Merchants DataTable
     * @return datatable
     */
    public function merchants_home()
    {

        $data['result'] = User::find(@Auth::user()->id);

        $merchant = Merchant::where('user_id', $data['result']->id)->first();

        $data['merchant'] = $merchant;

        $data['merchant']['address'] = DriverAddress::where('user_id', $data['result']->id)->first();

        $data['date'] = Carbon::today()->toDateString();

        $orders = DB::table('delivery_orders')->where('delivery_orders.merchant_id', $merchant->id)->where('delivery_orders.deleted_at', null)->where(function ($query) {
            $query->where('delivery_orders.status', 'pre_order')
                ->orWhere('delivery_orders.created_at', '>=', Carbon::today()->toDateString());
        })
            ->leftJoin('users as drivers', 'drivers.id', '=', 'delivery_orders.driver_id')
            ->leftJoin('users as customers', 'customers.id', '=', 'delivery_orders.customer_id')
            ->leftJoin('request', 'request.id', '=', 'delivery_orders.ride_request')
            ->select([
                'delivery_orders.id as id',
                'delivery_orders.created_at as created_at',
                DB::raw('DATE_ADD(delivery_orders.created_at, INTERVAL delivery_orders.estimate_time MINUTE) as delivery_time'),
                'delivery_orders.status', 'request.drop_location as drop_location',
                'delivery_orders.eta as eta',
                'drivers.first_name as first_name',
                DB::raw('CONCAT("+",drivers.country_code," ",drivers.mobile_number) as driver_phone'),
                DB::raw('CONCAT(customers.first_name," ",customers.last_name) as customer_name'),
            ]);

        $new_orders = clone $orders;
        $assigned_orders = clone $orders;
        $pickedup_orders = clone $orders;
        $delivered_orders = clone $orders;

        $data['orders']['new'] = $new_orders->whereIn('delivery_orders.status', ['new', 'pre_order'])->get();

        $data['orders']['assigned'] = $assigned_orders->where('delivery_orders.status', 'assigned')->get();

        $data['orders']['picked_up'] = $pickedup_orders->where('delivery_orders.status', 'picked_up')->get();

        $data['orders']['delivered'] = $delivered_orders->where('delivery_orders.status', 'delivered')->get();

        return view('merchants.tracking_order_details', $data)->with('title', 'Tracking Orders');
    }

    public function merchant_forget_password()
    {
        return view('merchants.forget_password')->with('title', 'Forgot password');
    }

    public function update_password(Request $request)
    {
        if ($request->isMethod("GET")) {
            $data['result'] = User::find(@Auth::user()->id);
            return view('merchants.password_update',$data);
        }

        if ($request->isMethod("POST")) {
            $user = User::find(@Auth::user()->id);

            if($user) {
                if(Hash::check($request->currPass, $user->password)) {

                    if($request->pass1 == $request->pass2) {
                        $user->password = $request->pass1;
                        $user->save();
                        flashMessage('success', trans('messages.user.pswrd_chnge'));
                        return redirect('merchants/home');
                    }
                    else {
                        return back()->withErrors(["pass2" => trans('messages.user.not_match_paswrd')]);
                    }
        
                }
                else {
                       return back()->withErrors(["currPass" => trans('messages.user.no_paswrd')]);
                }
            }
            else {
                return back()->withErrors(["password" => trans('messages.errors.unsuccessful')]);
            }
        }
    }

    public function show_reset_password(Request $request)
    {

        $password_resets = PasswordResets::whereToken($request->secret)->first();
        $user = User::where('email', @$password_resets->email)->first();
        if ($password_resets) {
            $password_result = $password_resets;

            $datetime1 = new DateTime();
            $datetime2 = new DateTime($password_result->created_at);
            $interval = $datetime1->diff($datetime2);
            $hours = $interval->format('%h');

            if ($hours >= 1) {
                // Delete used token from password_resets table
                PasswordResets::whereToken($request->secret)->delete();

                return redirect("merchants/new_login")->withErrors(['error' => trans('messages.user.token')]);

            }

            $data['result'] = User::whereEmail($password_result->email)->first();
            $data['token'] = $request->secret;
            return view('merchants.reset_password', $data);
        } else {

            return redirect("merchants/new_login")->withErrors(['error' => trans('messages.user.invalid_token')]);
        }
    }

    public function submit_password_reset(Request $request)
    {
        $rules = array(
            'new_password' => 'required|min:6|max:30',
            'confirm_password' => 'required|same:new_password',
        );

        // Password validation custom Fields name
        $niceNames = array(
            'new_password' => trans('messages.user.new_paswrd'),
            'confirm_password' => trans('messages.user.cnfrm_paswrd'),
        );

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($niceNames);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput(); // Form calling with Errors and Input values
        } else {

            // Delete used token from password_resets table
            $password_resets = PasswordResets::whereToken($request->token)->delete();

            $user = User::find($request->id);

            $user->password = $request->new_password;

            $user->save(); // Update Password in users table

            Session::flash('success', trans('messages.user.pswrd_chnge'));
            return redirect('merchants/new_login');

        }
    }

    public function merchant_reset_password(Request $request, EmailController $email_controller)
    {
        $user = User::whereEmail($request->email)->first();

        if ($user) {

            $email_controller->forgot_password_link($user);
            Session::flash('success', trans('messages.user.link') . $user->email);
            return redirect('merchants/new_login');
        } else {
            return back()->withErrors(["Email" => "Wrong email"]);
        }
    }
    public function merchant_new_login()
    {
        return view('merchants.new_login')->with('title', 'Login');
    }

    /**
     * Add a New Home Delivery Order
     *
     * @param array $request  Input values
     * @return redirect     to Home Delivery Order view
     */
    public function add(Request $request, $id = null)
    {
        if ($request->isMethod("GET")) {

            if (LOGIN_USER_TYPE == 'company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }
            $data['integrations'] = MerchantIntegrationType::pluck('name', 'id');
            $data['country_code_option'] = Country::select('long_name', 'phone_code')->get();

            return view('admin.merchant.add', $data);
        }

        if ($request->isMethod("POST")) {
            // Add Merchant Validation Rules
            $rules = array(
                'name' => 'required',
                'description' => 'required',
                'cuisine_type' => 'required',
                'integration_type' => 'required',
                'base_fee' => 'required',
                'base_distance' => 'required',
                'surchange_fee' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'password' => 'required',
                'mobile_number' => 'required|regex:/[0-9]{6}/',
                'country_code' => 'required',
            );

            switch ($request->integration_type) {
                case 2: // Square Up
                case 3: // Shopify
                    $rules['shared_secret'] = 'required';
                    break;
            }

            // Add Merchant Validation Custom Names
            $attributes = array(
                'name' => 'Merchant Name',
                'description' => 'Description',
                'cuisine_type' => 'Type of Cuisine',
                'integration_type' => 'Integration Type',
                'base_fee' => 'Base fee',
                'base_distance' => 'Base distance',
                'surchange_fee' => 'Surchange fee',
                'first_name' => trans('messages.user.firstname'),
                'last_name' => trans('messages.user.lastname'),
                'email' => trans('messages.user.email'),
                'mobile_number' => trans('messages.profile.phone'),
                'country_code' => trans('messages.user.country_code'),
                'password' => trans('messages.user.paswrd'),
            );
            // Edit Merchant Validation Custom Fields message
            $messages = array(
                'required' => ':attribute is required.',
                'mobile_number.regex' => trans('messages.user.mobile_no'),
            );
            $validator = Validator::make($request->all(), $rules, $messages, $attributes);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput(); // Form calling with Errors and Input values
            }

            $country_code = $request->country_code;

            $user = User::where('mobile_number', $request->mobile_number)->first();
            if (!$user) {
                $user = new User;
                $usedRef = User::find($request->referrer_id);

                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;

                if ($usedRef) {
                    $user->used_referral_code = $usedRef->referral_code;
                } else {
                    $user->used_referral_code = 0;
                }

                $user->email = $request->email;
                $user->country_code = $country_code;
                $user->password = $request->password;

                if ($request->mobile_number != "") {
                    $user->mobile_number = $request->mobile_number;
                }
                $user->user_type = $request->user_type;

                if ($user->save()) {
                    //Register to GrowSurf
                    // $referral_service = resolve('App\Services\ReferralPrograms\GrowSurf');
                    // $response = $referral_service->createUser([
                    //     'firstName' => $user->first_name,
                    //     'lastName' => $user->last_name,
                    //     'email' => $user->email
                    // ]);
                    // if(!isset($response->errors) && !empty($response)) {
                    //     DB::table('users')->where('id',$user->id)->update(['growsurf_id' => $response->id]);
                    // }
                }

                //find user by refferer_id
                if ($usedRef) {
                    //if there is no reference between users, create it
                    $referrel_user = new ReferralUser;
                    $referrel_user->referral_id = $user->id;
                    $referrel_user->user_id = $usedRef->id;
                    $referrel_user->user_type = $usedRef->user_type;
                    $referrel_user->save();
                }

                $user_address = new DriverAddress;

                $user_address->user_id = $user->id;
                $user_address->address_line1 = $request->address_line1 ? $request->address_line1 : '';
                $user_address->address_line2 = $request->address_line2 ? $request->address_line2 : '';
                $user_address->city = $request->city ? $request->city : '';
                $user_address->state = $request->state ? $request->state : '';
                $user_address->postal_code = $request->postal_code ? $request->postal_code : '';
                $user_address->save();
            }

            $merchant = new Merchant;

            $merchant->user_id = $user->id;
            $merchant->name = $request->name;
            $merchant->description = $request->description;
            $merchant->cuisine_type = $request->cuisine_type;
            $merchant->integration_type = $request->integration_type;
            $merchant->delivery_fee = $request->base_fee;
            $merchant->delivery_fee_per_km = $request->surchange_fee;
            $merchant->delivery_fee_base_distance = $request->base_distance;
            switch ($request->integration_type) {
                case 1: // Gloria Food
                    $merchant->shared_secret = Str::uuid();
                    break;
                case 2: // Square Up
                    // curl initiate
                    $ch = curl_init();

                    // API URL to send data
                    $url = "https://connect.squareup.com/v2/merchants";

                    if (App::environment(['local', 'development'])) {
                        $url = 'https://connect.squareupsandbox.com/v2/merchants';
                    }
                    curl_setopt($ch, CURLOPT_URL, $url);

                    // SET Header
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Square-Version: 2020-04-22',
                        'Authorization: Bearer ' . $request->shared_secret,
                        'Content-Type: application/json'));

                    // SET Method as a POST
                    curl_setopt($ch, CURLOPT_POST, false);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    // Execute curl and assign returned data
                    $response = curl_exec($ch);
                    $tmp = json_decode($response);
                    $square_merchant = $tmp->merchant[0];
                    $merchant->squareup_id = $square_merchant->id;
                    $merchant->shared_secret = $request->shared_secret;

                    // Close curl
                    curl_close($ch);
                    break;
                case 3: // Shopify
                    $merchant->shared_secret = $request->shared_secret;
                    break;
                case 4: // CloudWaitress
                    $merchant->shared_secret = $request->shared_secret;
                    break;
                case 5: // Yelo
                    $merchant->shared_secret = $request->shared_secret;
                    break;
            }
            $merchant->save();

            flashMessage('success', 'Merchant created');

            return redirect(LOGIN_USER_TYPE . '/merchants');
        }

        return redirect(LOGIN_USER_TYPE . '/merchants');
    }

    /**
     * Add a New Home Delivery Order
     *
     * @param array $request  Input values
     * @return redirect     to Home Delivery Order view
     */
    public function addDelivery(Request $request, $id = null)
    {
        if ($request->isMethod("GET")) {
            //Inactive Company could not add driver
            if (LOGIN_USER_TYPE == 'company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }

            $timezone = date_default_timezone_get();

            $date_obj = \Carbon\Carbon::now()->setTimezone($timezone);

            $data['timezon'] = $timezone;
            $data['country_code_option'] = Country::select('long_name', 'phone_code')->get();
            // test($data['country_code_option']);

            if (LOGIN_USER_TYPE == 'company' && session()->get('currency') != null) {
                $default_currency = Currency::whereCode(session()->get('currency'))->first();
            } else {
                $default_currency = view()->shared('default_currency');
            }
            $data['currency_symbol'] = html_string($default_currency->symbol);

            $merchant_id = Auth::user()->id;
            $merchantAddress = DriverAddress::where('user_id', $merchant_id)->first();
            $fullMerchantAddress = "{$merchantAddress->address_line1} {$merchantAddress->address_line2}, {$merchantAddress->city} {$merchantAddress->state} {$merchantAddress->postal_code}, Australia";
            //Building 57, Carlton VIC 3053, Australia
            $getData = DB::table('merchants')->select('id', 'name')->where('user_id', $merchant_id)->first();
            return view('merchants.add', $data)->with('merchant_name', $getData->name)->with('merchant_id', $getData->id)->with('merchant_address', $fullMerchantAddress)->with('title', 'Tracking Orders');
        }

        if ($request->isMethod("POST")) {
            // Add Driver Validation Rules

            $rules = array(
                'pick_up_location' => 'required',
                'drop_off_location' => 'required',
                'estimate_time' => 'required',
                'customer_name' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'customer_phone_number' => 'required',
                'mobile_number' => 'required',
                'pick_up_latitude' => 'required',
                'pick_up_longitude' => 'required',
                'drop_off_latitude' => 'required',
                'drop_off_longitude' => 'required',
                'merchant_id' => 'required',
            );

            // Add Driver Validation Custom Names
            $attributes = array(
                'estimate_time' => 'Estimate Time',
                'pick_up_location' => 'Pick Up Location',
                'drop_off_location' => 'Drop Off Location',
                'customer_name' => 'Customer Name',
                'customer_phone_number' => 'Customer Phone Number',
                'pick_up_latitude' => 'Pick Up Latitude',
                'pick_up_longitude' => 'Pick Up Longitude',
                'drop_off_latitude' => 'Drop Off Latitude',
                'drop_off_longitude' => 'Drop Off Longitude',
                'merchant_id' => 'Merchant',
            );

            // Edit Rider Validation Custom Fields message
            $messages = array(
                'required' => ':attribute is required.',
            );
            $validator = Validator::make($request->all(), $rules, $messages, $attributes);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $user = $this->get_or_create_rider($request);

            $ride_request = $this->create_ride_request($request, $user);

            //create order
            $order = new HomeDeliveryOrder;

            //calculate distance
            if (HERE_REST_KEY != "") {
                $req = (object) array("origin_latitude" => $request->drop_off_latitude, "origin_longitude" => $request->drop_off_longitude, "destination_latitude" => $request->pick_up_latitude, "destination_longitude" => $request->pick_up_longitude);
                $get_fare_estimation['distance'] = $this->map_service->getDistance($req);
            } else {
                $get_fare_estimation = $this->request_helper->GetDrivingDistance($request->pick_up_latitude, $request->drop_off_latitude, $request->pick_up_longitude, $request->drop_off_longitude);
                if ($get_fare_estimation['status'] == "success") {
                    if ($get_fare_estimation['distance'] == '') {
                        $get_fare_estimation['distance'] = 0;
                    }
                } else {
                    $get_fare_estimation['distance'] = 0;
                }
            }

            $order->distance = $get_fare_estimation['distance'];
            $order->estimate_time = $request->estimate_time;

            if ($order->estimate_time > 60) {
                $order->status = 'pre_order';
            }

            $order->customer_id = $user->id;
            $order->ride_request = $ride_request->id;

            if ($request->driver_id) {
                $order->driver_id = $request->driver_id;
                $order->status = 'assigned';
            }

            $order->merchant_id = $request->merchant_id;

            $merchant = Merchant::where('id', $request->merchant_id)->first();
            $order->order_description = $request->order_description;
            $fee = 0.0;
            if ($request->fee) {
                $fee = (float) $request->fee;
            } else {
                if (($get_fare_estimation['distance'] / 1000) > $merchant->delivery_fee_base_distance) {
                    $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance'] / 1000 - $merchant->delivery_fee_base_distance);
                } else {
                    $fee = $merchant->delivery_fee;
                }
            }
            $order->fee = round($fee, 2);

            $order->save();

            flashMessage('success', 'Order successfully added, Sending push messages to nearest drivers...');
            if ($request->driver_id) {
                $driver_details = User::where('id', $request->driver_id)->first();
                $message = "You are assigned to deliver order #" . $order->id . ". Please, check your deliveries.";
                $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message, true);
                try {
                    $trip_status = $this->schedule_delivery_trip($order, $driver_details);
                } catch (\Exception $e) {
                    //
                }
            } else {
                $this->notify_drivers($request, 'New job(s) in your location', false);
            }

            flashMessage('success', 'Delivery created');

            return redirect(LOGIN_USER_TYPE . '/home');
        }

        return redirect(LOGIN_USER_TYPE . '/add_delivery');
    }

    /**
     * Get merchant function
     *
     * @return user list
     */
    public function get_send_merchant()
    {
        $merchant_id = Auth::user()->id;
        return DB::table('merchants')->select('id', 'name')->where('user_id', $merchant_id)->first();
    }

    /**
     * Get driver function
     *
     * @return user list
     */
    public function get_send_driver($id)
    {
        $driver_id = Auth::user()->id;
        return DB::table('users')->select('id', 'first_name', 'last_name', 'country_code', 'mobile_number')
            ->where('user_type', 'driver')->where('status', 'Active')->where('id', $driver_id)->first();
    }

    /**
     * Update Merchants
     *
     * @param array $request    Input values
     * @return redirect     to Home Delivery Order View
     */
    public function update(Request $request)
    {
        if ($request->isMethod("GET")) {
            //Inactive Company could not add driver
            if (LOGIN_USER_TYPE == 'company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }

            $data['result'] = Merchant::find($request->id);
            $data['result_info'] = User::find($data['result']->user_id);

            if ($data['result']) {

                $data['integrations'] = MerchantIntegrationType::pluck('name', 'id');
                $data['base_fee'] = $data['result']->delivery_fee;
                $data['surchange_fee'] = $data['result']->delivery_fee_per_km;
                $data['base_distance'] = $data['result']->delivery_fee_base_distance;

                $data['address'] = DriverAddress::where('user_id', $data['result']->user_id)->first();
                $data['country_code_option'] = Country::select('long_name', 'phone_code')->get();

                $usedRef = User::where('referral_code', $data['result_info']->used_referral_code)->first();
                if ($usedRef) {
                    $data['referrer_id'] = $usedRef->id;
                } else {
                    $data['referrer_id'] = null;
                }

                return view('admin.merchant.edit', $data);
            }

            flashMessage('danger', 'Invalid ID');
            return redirect(LOGIN_USER_TYPE . '/merchants');
        }

        if ($request->isMethod("POST")) {
            // Edit Driver Validation Rules
            $rules = array(
                'name' => 'required',
                'description' => 'required',
                'cuisine_type' => 'required',
                'integration_type' => 'required',
                'base_fee' => 'required',
                'base_distance' => 'required',
                'surchange_fee' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'referral_code' => 'required',
                'country_code' => 'required',
            );

            switch ($request->integration_type) {
                case 2: // Square Up
                case 3: // Shopify
                    $rules['shared_secret'] = 'required';
                    break;
            }

            // Edit Driver Validation Custom Names
            $attributes = array(
                'name' => 'Name',
                'description' => 'Description',
                'integration_type' => 'Integration Type',
                'base_fee' => 'Base fee',
                'base_distance' => 'Base distance',
                'surchange_fee' => 'Surchange fee',
                'cuisine_type' => 'Type of Cuisine',
                'first_name' => trans('messages.user.firstname'),
                'last_name' => trans('messages.user.lastname'),
                'email' => trans('messages.user.email'),
                'status' => trans('messages.driver_dashboard.status'),
                'mobile_number' => trans('messages.profile.phone'),
                'country_code' => trans('messages.user.country_code'),
            );

            // Edit Rider Validation Custom Fields message
            $messages = array(
                'required' => ':attribute is required.',
                'mobile_number.regex' => trans('messages.user.mobile_no'),
            );
            $validator = Validator::make($request->all(), $rules, $messages, $attributes);

            $merchant = Merchant::find($request->id);
            $user_id = $merchant->user_id;

            $validator->after(function ($validator) use ($request, $user_id) {
                //--- Konstantin N edits: refferal checking for coincidence
                $referral_c = User::where('referral_code', $request->referral_code)->where('user_type', $request->user_type)->where('id', '!=', $user_id)->count();

                if ($referral_c) {
                    $validator->errors()->add('referral_code', trans('messages.referrals.referral_exists'));
                }
            });

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput(); // Form calling with Errors and Input values
            }

            // $merchant = Merchant::find($request->id);

            $merchant->name = $request->name;
            $merchant->description = $request->description;
            $merchant->cuisine_type = $request->cuisine_type;
            $merchant->integration_type = $request->integration_type;
            $merchant->delivery_fee = $request->base_fee;
            $merchant->delivery_fee_per_km = $request->surchange_fee;
            $merchant->delivery_fee_base_distance = $request->base_distance;
            $merchant->stripe_id = $request->stripe_id;
            switch ($request->integration_type) {
                case 2: // Square Up
                    // curl initiate
                    $ch = curl_init();

                    // API URL to send data
                    $url = "https://connect.squareup.com/v2/merchants";

                    if (App::environment(['local', 'development'])) {
                        $url = 'https://connect.squareupsandbox.com/v2/merchants';
                    }
                    curl_setopt($ch, CURLOPT_URL, $url);

                    // SET Header
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Square-Version: 2020-04-22',
                        'Authorization: Bearer ' . $request->shared_secret,
                        'Content-Type: application/json'));

                    // SET Method as a POST
                    curl_setopt($ch, CURLOPT_POST, false);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    // Execute curl and assign returned data
                    $response = curl_exec($ch);
                    $tmp = json_decode($response);
                    try {
                        $square_merchant = $tmp->merchant[0];
                        $merchant->squareup_id = $square_merchant->id;
                        $merchant->shared_secret = $request->shared_secret;

                        // Close curl
                        curl_close($ch);
                    } catch (\Exception $e) {
                        flashMessage('danger', $tmp->errors[0]->category . " : " . $tmp->errors[0]->code . " Square Up ERROR: " . $tmp->errors[0]->detail);
                        return back();
                    }
                    break;
                case 3: // Shopify
                    $merchant->shared_secret = $request->shared_secret;
                    break;
                case 4: // CloudWaitress
                    $merchant->shared_secret = $request->shared_secret;
                    break;
                case 5: // Yelo
                    $merchant->shared_secret = $request->shared_secret;
                    break;
            }
            $merchant->save();

            $country_code = $request->country_code;

            $user = User::find($merchant->user_id);

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->country_code = $country_code;
            $user->referral_code = $request->referral_code;

            if ($request->password != '') {
                $user->password = $request->password;
            }

            //find user by refferer_id
            $usedRef = User::find($request->referrer_id);
            if ($usedRef) {
                //remove old reference if used referral code updated
                if ($usedRef->used_referral_code != $user->used_referral_code) {
                    $old_reffered = User::where('referral_code', $user->used_referral_code)->first();
                    if ($old_reffered) {
                        $reference = ReferralUser::where('user_id', $old_reffered->id)->where('referral_id', $user->id)->first();
                        if ($reference) {
                            $reference->delete();
                        }
                    }
                }

                //get reffernce between referred user and current user
                $reference = ReferralUser::where('user_id', $usedRef->id)->where('referral_id', $user->id)->first();

                if (!$reference) {
                    //if there is no reference between users, create it
                    $referrel_user = new ReferralUser;
                    $referrel_user->referral_id = $user->id;
                    $referrel_user->user_id = $usedRef->id;
                    $referrel_user->user_type = $usedRef->user_type;
                    $referrel_user->save();
                }

                $user->used_referral_code = $usedRef->referral_code;

            }

            if ($request->mobile_number != "") {
                $user->mobile_number = $request->mobile_number;
            }
            $user->user_type = $request->user_type;

            $user->save();

            $user_address = DriverAddress::where('user_id', $user->id)->first();
            if ($user_address == '') {
                $user_address = new DriverAddress;
            }

            $user_address->user_id = $user->id;
            $user_address->address_line1 = $request->address_line1;
            $user_address->address_line2 = $request->address_line2 ? $request->address_line2 : '' ;
            $user_address->city = $request->city;
            $user_address->state = $request->state;
            $user_address->postal_code = $request->postal_code;
            $user_address->save();

            flashMessage('success', 'Merchant data successfully updated');

            return redirect(LOGIN_USER_TYPE . '/merchants');
        }

        return redirect(LOGIN_USER_TYPE . '/merchants');
    }

    /**
     * Delete Order
     *
     * @param array $request    Input values
     * @return redirect     to Order View
     */
    public function delete(Request $request)
    {
        $result = $this->canDestroy($request->id);

        if ($result['status'] == 0) {
            flashMessage('error', $result['message']);
            return back();
        }

        try {
            $merchant = Merchant::find($request->id);
            $contact_info = User::find($merchant->user_id);
            DriverAddress::where('user_id', $contact_info->id)->delete();
            $contact_info->delete();
            $merchant->delete();
        } catch (\Exception $e) {
            flashMessage('error', 'Got a problem on deleting this merchant. Contact admin, please');
            return back();
        }

        flashMessage('success', 'Deleted Successfully');
        return redirect(LOGIN_USER_TYPE . '/merchants');
    }

    // Check Given Order deletable or not
    public function canDestroy($order_id)
    {
        if ($order_id == 1) {
            $return = array('status' => '0', 'message' => 'Default merchant can\'t be deleted');
        } else {
            $return = array('status' => '1', 'message' => '');
        }

        return $return;
    }

    /**
     * Display a referral detail
     *
     * @return \Illuminate\Http\Response
     */
    public function merchant_order_details(Request $request)
    {
        $data['merchant_orders'] = HomeDeliveryOrder::where('merchant_id', $request->id)
            ->join('users as rider', function ($join) {
                $join->on('rider.id', '=', 'delivery_orders.customer_id');
            })
            ->join('request as ride_request', function ($join) {
                $join->on('ride_request.id', '=', 'delivery_orders.ride_request');
            })
            ->join('merchants', function ($join) {
                $join->on('merchants.id', '=', 'delivery_orders.merchant_id');
            })
            ->select([
                'delivery_orders.id as id',
                DB::raw('CONCAT(delivery_orders.estimate_time," mins") as estimate_time'),
                'delivery_orders.driver_id as driver_id',
                'delivery_orders.created_at as created_at',
                DB::raw('CONCAT(delivery_orders.distance/1000," KM") as distance'),
                'merchants.name as merchant_name',
                'delivery_orders.order_description as order_description',
                DB::raw('CONCAT(delivery_orders.estimate_time," mins") as estimate_time'),
                'delivery_orders.fee as fee',
                'delivery_orders.status as status',
                'ride_request.pickup_location as pick_up_location',
                'ride_request.drop_location as drop_off_location',
                DB::raw('CONCAT(rider.first_name," ",rider.last_name) as customer_name'),
                DB::raw('CONCAT("+",rider.country_code,rider.mobile_number) as mobile_number'),
            ])
            ->get();

        if ($data['merchant_orders']->count() == 0) {
            flashMessage('error', 'Invalid ID');
            return back();
        }

        $data['merchant_name'] = Merchant::where('id', $request->id)
            ->get('name')
            ->first()
            ->name;

        return view('admin.delivery_order.details', $data);
    }

    /**
     * Import merchants from csv
     *
     * @param array $request  csv file
     * @return redirect     to Import merchants view
     */
    public function import_merchants(Request $request)
    {
        if (!$_POST) {
            return view('admin.imports.import_merchant.import');
        } else {

            if ($request->input('submit') != null) {

                $file = $request->file('file');

                // File Details
                $filename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $tempPath = $file->getRealPath();
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();

                // Valid File Extensions
                $valid_extension = array("csv");

                // Check file extension
                if (in_array(strtolower($extension), $valid_extension)) {

                    // File upload location
                    $location = 'uploads';

                    // Upload file
                    $file->move($location, $filename);

                    // Import CSV to Database
                    $filepath = public_path($location . "/" . $filename);

                    // Reading file
                    $file = fopen($filepath, "r");

                    $importData_arr = array();
                    $i = 0;

                    while (($filedata = fgetcsv($file, 1000, ",")) !== false) {
                        $num = count($filedata);

                        // Skip first row (Remove below comment if you want to skip the first row)
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    fclose($file);

                    $users_inserted = 0;

                    // Insert to MySQL database
                    foreach ($importData_arr as $index => $importData) {
                        if (isset($importData[0])) {

                            $referral_code = $importData[0];
                            $merchant_name = $importData[1];
                            $used_referral = $importData[2];
                            $first_name = $importData[3];
                            $last_name = $importData[4];
                            $email = $importData[6];
                            $mobile_number = $importData[7];
                            $address_line1 = isset($importData[8]) ? $importData[8] : '';
                            $address_line2 = isset($importData[9]) ? $importData[9] : '';
                            $city = isset($importData[10]) ? $importData[10] : '';
                            $state = isset($importData[11]) ? $importData[11] : '';
                            $postal_code = isset($importData[12]) ? $importData[12] : '';
                            $cuisine_type = isset($importData[14]) ? $importData[14] : '';

                            $user_data = null;

                            $user_count = User::where('email', $email)->count();

                            $user_data = null;

                            $address_data = [
                                'address_line1' => $address_line1,
                                'address_line2' => $address_line2,
                                'city' => $city,
                                'state' => $state,
                                'postal_code' => $postal_code,
                            ];

                            if ($user_count) {
                                $user_data = [
                                    'first_name' => $first_name,
                                    'last_name' => $last_name,
                                    'email' => $email,
                                    "country_code" => '61',
                                    'referral_code' => $referral_code,
                                ];
                            } else {
                                $user_data = [
                                    "first_name" => $first_name,
                                    "last_name" => $last_name,
                                    "email" => $email,
                                    "country_code" => '61',
                                    "mobile_number" => $mobile_number,
                                    "password" => bin2hex(openssl_random_pseudo_bytes(8, $crypto)),
                                    "user_type" => "Merchant",
                                    "company_id" => 1,
                                    "status" => 'Pending',
                                    'referral_code' => $referral_code,
                                ];
                            }

                            $user = User::where('email', $email)->where('user_type', 'Merchant')->first();

                            if (!$user) {
                                $user = new User;
                                $user->user_type = "Merchant";
                            }
                            $user->save();

                            $usedRef = User::where('referral_code', $used_referral)->first();
                            if ($usedRef) {
                                $user->used_referral_code = $used_referral;
                                $reff = ReferralUser::where('user_id', $usedRef->id)->where('referral_id', $user->id)->count();
                                if (!$reff) {
                                    $referrel_user = new ReferralUser;
                                    $referrel_user->referral_id = $user->id;
                                    $referrel_user->user_id = $usedRef->id;
                                    $referrel_user->user_type = $usedRef->user_type;
                                    $referrel_user->save();
                                }
                            } else {
                                $user->used_referral_code = 0;
                            }

                            $user->save();

                            User::where('id', $user->id)->update($user_data);

                            $address = DriverAddress::where('user_id', $user->id)->first();
                            if (!$address) {
                                $address = new DriverAddress;
                                $address->user_id = $user->id;
                                $address->save();
                            }
                            DriverAddress::where('id', $address->id)->update($address_data);

                            $merchant_count = Merchant::where('name', $merchant_name)->count();

                            if ($merchant_count) {
                                $merchant_data = [
                                    'name' => $merchant_name,
                                    'cuisine_type' => $cuisine_type,
                                ];
                            } else {
                                $merchant_data = [
                                    'name' => $merchant_name,
                                    'cuisine_type' => $cuisine_type,
                                    'user_id' => $user->id,
                                    'description' => '',
                                    'integration_type' => 1,
                                    'delivery_fee' => 8.95,
                                    'delivery_fee_per_km' => 1.00,
                                    'delivery_fee_base_distance' => 5.00,
                                    'shared_secret' => Str::uuid(),
                                ];
                            }

                            $merchant = Merchant::where('name', $merchant_name)->first();

                            if (!$merchant) {
                                $merchant = new Merchant;
                                $merchant->name = $merchant_name;
                                $merchant->integration_type = 1;
                                $merchant->user_id = $user->id;
                                $merchant->save();
                            }

                            Merchant::where('id', $merchant->id)->update($merchant_data);

                            $users_inserted += 1;
                        }
                    }

                    //Send response
                    $this->helper->flash_message('success', 'Succesfully imported: ' . $users_inserted . ' users'); // Call flash message function

                    return redirect(LOGIN_USER_TYPE . '/import_leaders');
                } else {
                    //Send response
                    $this->helper->flash_message('danger', 'Invalid file type'); // Call flash message function

                    return redirect(LOGIN_USER_TYPE . '/import_leaders');
                }
            }
        }
    }

    /**
     * custom push notification
     *
     * @return success or fail
     */
    public function send_custom_pushnotification($device_id, $device_type, $user_type, $message, $isAssigned)
    {
        if (LOGIN_USER_TYPE == 'company') {
            $push_title = "Message from " . Auth::guard('company')->user()->name;
        } else {
            $push_title = "Message from " . SITE_NAME;
        }

        if (!isset($isAssigned)) {
            $isAssigned = false;
        }

        try {
            if ($device_type == 1) {
                $data = array('custom_message' => array('title' => $message, 'push_title' => $push_title, "isAssigned" => $isAssigned));
                $this->request_helper->push_notification_ios($message, $data, $user_type, $device_id, $admin_msg = 1);
            } else {
                $data = array('custom_message' => array('message_data' => $message, 'title' => $push_title, "isAssigned" => $isAssigned));
                $this->request_helper->push_notification_android($push_title, $data, $user_type, $device_id, $admin_msg = 1);
            }
        } catch (\Exception $e) {
            logger('Could not send push notification');
        }
    }

    /**
     * Create new rider function
     *
     * @return success or fail
     */
    public function get_or_create_rider($request)
    {
        //Create user for correct payment calculation
        $language = $request->language ?? 'en';
        App::setLocale($language);

        $user = User::where('mobile_number', $request->mobile_number)
            ->where('user_type', 'Rider')->first();

        if (!$user) {
            $user = new User;
            $user->mobile_number = $request->mobile_number;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->user_type = 'Rider';
            $user->password = $request->password;
            $user->country_code = $request->country_code;
            $user->language = $language;
            $user->email = $request->mobile_number . '@rideon.group';
            $user->currency_code = get_currency_from_ip();

            $user->save();
        }
        return $user;
    }

    /**
     * Create ride request.
     * Ride request table stores pick up and drop locations
     *
     * @return success or fail
     */
    public function create_ride_request($request, $user)
    {
        $polyline = $this->request_helper->GetPolyline($request->pick_up_latitude, $request->drop_off_latitude, $request->pick_up_longitude, $request->drop_off_longitude);
        //create ride request
        $ride_request = new RideRequest;
        $ride_request->user_id = $user->id;
        $ride_request->group_id = null;
        $ride_request->pickup_latitude = $request->pick_up_latitude;
        $ride_request->pickup_longitude = $request->pick_up_longitude;
        $ride_request->drop_latitude = $request->drop_off_latitude;
        $ride_request->drop_longitude = $request->drop_off_longitude;
        $ride_request->driver_id = User::where('user_type', 'Driver')->first()->id;
        $ride_request->car_id = '1';
        $ride_request->pickup_location = $request->pick_up_location;
        $ride_request->drop_location = $request->drop_off_location;
        $ride_request->trip_path = $polyline;
        $ride_request->payment_mode = 'Stripe';
        $ride_request->status = 'Accepted';
        //TODO: change to default timezone
        $ride_request->timezone = 'Australia/Melbourne';
        $ride_request->location_id = '1';
        $ride_request->additional_fare = '';
        $ride_request->peak_fare = site_settings('manual_surchange');
        $ride_request->save();

        return $ride_request;
    }

    /**
     * Shcedule delivery trip
     *
     * @return success or fail
     */
    public function schedule_delivery_trip($order, $driver)
    {
        $rider = User::where('id', $order->customer_id)->first();
        $ride_request = RideRequest::where('id', $order->ride_request)->first();

        //Insert record in Trips table
        $trip = Trips::where('request_id', $ride_request->id)->where('status', '!=', 'Cancelled')->first();
        if (!$trip) {
            $trip = new Trips;
            $trip->trip_type = 'Delivery';
            $trip->user_id = $rider->id;
            $trip->otp = mt_rand(1000, 9999);
            $trip->driver_id = $driver->id;
            $trip->car_id = $ride_request->car_id;
            $trip->request_id = $ride_request->id;
            $trip->payment_mode = 'Stripe';
            $trip->status = 'Scheduled';
            $trip->currency_code = 'AUD'; //TODO: fix currency code to dynamic on driver $driver->currency_code;
            $trip->peak_fare = $ride_request->peak_fare;
            $trip->subtotal_fare = $order->fee * 1.1;
            $trip->arrive_time = \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s');

            $subscription = DriversSubscriptions::where('user_id', $driver->id)
                ->whereNotIn('status', ['canceled'])
                ->first();

            $plan = StripeSubscriptionsPlans::where('id', $subscription->plan)->first();

            $tax_gst = 1 + site_settings('tax_gst') / 100; //tax or gst
            if ($plan->plan_name == 'Driver Only') {
                $commission_percent = site_settings('regular_driver_booking_fee') / 100;
                $commission = $order->fee * $commission_percent;
                $trip->driver_or_company_commission = $commission;
                $trip->driver_payout = ($order->fee - $commission) * $tax_gst;
                $trip->total_fare = $order->fee * $tax_gst;
            } else {
                $trip->driver_or_company_commission = 0.00;
                $trip->driver_payout = $order->fee * $tax_gst;
                $trip->total_fare = $order->fee * $tax_gst;
            }
            $trip->save();
        }

        return array([
            'status_code' => '1',
            'status_message' => 'Trip schedulled.',
        ]);
    }

    /**
     * Notify nearest drivers
     *
     * @return success or fail
     */
    public function notify_drivers($request, $message, $isAssigned)
    {
        if (!isset($isAssigned)) {
            $isAssigned = false;
        }
        $nearest_cars = DriverLocation::select(DB::raw('*, ( 6371 * acos( cos( radians(' . $request->pick_up_latitude . ') ) * cos( radians( latitude ) ) * cos(radians( longitude ) - radians(' . $request->pick_up_longitude . ') ) + sin( radians(' . $request->pick_up_latitude . ') ) * sin( radians( latitude ) ) ) ) as distance'))
            ->having('distance', '<=', 15)->get();

        foreach ($nearest_cars as $nearest_car) {
            $driver_details = User::where('id', $nearest_car->user_id)->first();

            if ($driver_details->device_id != "" && $driver_details->status == "Active") {
                $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message, $isAssigned);
            }
        }
    }
}
