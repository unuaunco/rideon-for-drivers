<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\DataTables\HomeDeliveryOrderDataTable;
use App\Models\HomeDeliveryOrder;
use App\Models\DriversSubscriptions;
use App\Models\StripeSubscriptionsPlans;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Country;
use App\Models\Company;
use App\Models\CarType;
use App\Models\ScheduleRide;
use App\Models\Request as RideRequest;
use App\Models\DriverLocation;
use App\Models\Trips;
use App\Models\Payment;

use App\Http\Start\Helpers;
use App\Http\Helper\RequestHelper;
use Carbon\Carbon;
use DateInterval;
use Validator;
use JWTAuth;
use DB;
use DateTime;
use App;
use Mail;

class HomeDeliveryController extends Controller
{

    protected $helper;  // Global variable for instance of Helpers

    public function __construct(RequestHelper $request)
    {
        $this->helper = new Helpers;
        $this->otp_helper = resolve('App\Http\Helper\OtpHelper');
        $this->map_service = resolve('App\Services\MapServices\HereMapService');
        $this->request_helper = $request;
    }

    /**
     * Load Datatable for Orders
     *
     * @param array $dataTable  Instance of HomeDelivery DataTable
     * @return datatable
     */
    public function index(HomeDeliveryOrderDataTable $dataTable)
    {
        return $dataTable->render('admin.delivery_order.view');
    }

    /**
     * Orders tracking page
     *
     */
    public function ordersDashboard(){

        $data['date'] = Carbon::today()->toDateString();

        $data['time_now'] = Carbon::now()->toDateTimeString();

        $orders = DB::table('delivery_orders')->where('delivery_orders.deleted_at', null)->where(function($query) {
                $query->whereIn('delivery_orders.status', ['new','pre_order', 'assigned','picked_up'])
                      ->orWhere('delivery_orders.created_at', '>=', Carbon::today()->toDateString());
            })
            ->leftJoin('users as drivers', 'drivers.id', '=', 'delivery_orders.driver_id')
            ->leftJoin('users as customers', 'customers.id', '=', 'delivery_orders.customer_id')
            ->leftJoin('request', 'request.id', '=', 'delivery_orders.ride_request')
            ->leftJoin('merchants', 'merchants.id', '=', 'delivery_orders.merchant_id')
            ->select([
                'delivery_orders.id as id',
                'delivery_orders.status as status',
                'merchants.name as merchant_name',
                'merchants.id as merchant_id',
                'delivery_orders.created_at as created_at',
                DB::raw('DATE_ADD(delivery_orders.created_at, INTERVAL delivery_orders.estimate_time MINUTE) as delivery_time'),
                'delivery_orders.status','request.drop_location as drop_location',
                'delivery_orders.status','request.pickup_location as pickup_location',
                'delivery_orders.eta as eta',
                'drivers.first_name as first_name',
                'drivers.id as driver_id',
                DB::raw('CONCAT(customers.first_name," ",customers.last_name) as customer_name'),
                'customers.id as customer_id',
            ]);

        $new_orders = clone $orders;
        $assigned_orders  = clone $orders;
        $pickedup_orders = clone $orders;
        $delivered_orders = clone $orders;

        $data['orders']['new'] = $new_orders->whereIn('delivery_orders.status',['new','pre_order'])->orderBy('delivery_time', 'asc')->get();

        $data['orders']['assigned'] = $assigned_orders->where('delivery_orders.status','assigned')->orderBy('delivery_time', 'asc')->get();

        $data['orders']['picked_up'] = $pickedup_orders->where('delivery_orders.status','picked_up')->orderBy('delivery_time', 'asc')->get();

        $data['orders']['delivered'] = $delivered_orders->where('delivery_orders.status','delivered')->orderBy('delivery_time', 'asc')->get();

        return view('admin.delivery_order.orders_dashboard',$data);
    }

    /**
     * Load Datatable for Delivered orders
     *
     * @param array $dataTable  Instance of HomeDelivery DataTable
     * @return datatable
     */
    public function index_delivered(DeliveredOrdersDataTable $dataTable)
    {
        return $dataTable->render('admin.delivery_order.delivered_view');
    }

    public function test(Request $request){
        $data = HomeDeliveryOrder::where('status','new')->get();
        $Order_ID_List = [];
        $now = Carbon::now();
        foreach($data as $order){
            $date_to = Carbon::createFromTimeString($order->created_at)
                ->addMinutes((int)$order->estimate_time);

            if($now->greaterThan($date_to)){
                array_push($Order_ID_List, $order->id);
            }
        }
        return response()->json( [ "Order_ID_List" => $Order_ID_List ]);

    }

    /**
     * Add a New Home Delivery Order
     *
     * @param array $request  Input values
     * @return redirect     to Home Delivery Order view
     */
    public function add(Request $request, $id=null)
    {
        if($request->isMethod("GET")) {
            //Inactive Company could not add driver
            if (LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }

            $timezone = date_default_timezone_get();

            $date_obj = \Carbon\Carbon::now()->setTimezone($timezone);

            $data['timezon'] = $timezone;
            $data['country_code_option'] = Country::select('long_name','phone_code')->get();

            if (LOGIN_USER_TYPE=='company' && session()->get('currency') != null) {
                $default_currency = Currency::whereCode(session()->get('currency'))->first();
            }
            else {
                $default_currency = view()->shared('default_currency');
            }
            $data['currency_symbol'] = html_string($default_currency->symbol);

            return view('admin.delivery_order.add',$data);
        }

        if($request->isMethod("POST")) {
            // Add Driver Validation Rules

            $rules = array(
                'estimate_time'     => 'required',
                'pick_up_location'  => 'required',
                'drop_off_location' => 'required',
                'customer_name'         => 'required',
                'customer_phone_number' => 'required',
                'pick_up_latitude'      => 'required',
                'pick_up_longitude'     => 'required',
                'drop_off_latitude'      => 'required',
                'drop_off_longitude'     => 'required',
                'merchant_id'            => 'required',
            );

            // Add Driver Validation Custom Names
            $attributes = array(
                'estimate_time'     => 'Estimate Time',
                'pick_up_location'  => 'Pick Up Location',
                'drop_off_location' => 'Drop Off Location',
                'customer_name'         => 'Customer Name',
                'customer_phone_number' => 'Customer Phone Number',
                'pick_up_latitude'      => 'Pick Up Latitude',
                'pick_up_longitude'     => 'Pick Up Longitude',
                'drop_off_latitude'      => 'Drop Off Latitude',
                'drop_off_longitude'     => 'Drop Off Longitude',
                'merchant_id'            => 'Merchant',
            );

            // Edit Rider Validation Custom Fields message
            $messages = array(
                'required'            => ':attribute is required.',
            );
            $validator = Validator::make($request->all(), $rules,$messages, $attributes);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $user = $this->get_or_create_rider($request);

            $ride_request = $this->create_ride_request($request, $user);

            //create order
            $order = new HomeDeliveryOrder;

            //calculate distance
            if(HERE_REST_KEY != ""){
                        $req = (object)array("origin_latitude" => $request->drop_off_latitude , "origin_longitude" => $request->drop_off_longitude, "destination_latitude" => $request->pick_up_latitude , "destination_longitude" => $request->pick_up_longitude);
                        $get_fare_estimation['distance'] = $this->map_service->getDistance($req);
            }
            else{
                $get_fare_estimation = $this->request_helper->GetDrivingDistance($request->pick_up_latitude, $request->drop_off_latitude,$request->pick_up_longitude, $request->drop_off_longitude);
                if ($get_fare_estimation['status'] == "success") {
                    if ($get_fare_estimation['distance'] == '') {
                        $get_fare_estimation['distance'] = 0;
                    }
                }
                else{
                    $get_fare_estimation['distance'] = 0;
                }
            }

            $order->distance                = $get_fare_estimation['distance'];
            $order->estimate_time           = $request->estimate_time;

            if($order->estimate_time > 60){
                $order->status              = 'pre_order';
            }

            $order->customer_id             = $user->id;
            $order->ride_request            = $ride_request->id;

            if($request->driver_id){
                $order->driver_id           = $request->driver_id;
                $order->status              = 'assigned';
            }

            $order->merchant_id             = $request->merchant_id;

            $merchant = Merchant::where('id', $request->merchant_id)->first();
            $order->order_description       = $request->order_description;
            $fee = 0.0;
            if($request->fee){
                $fee = (float)$request->fee;
            }
            else{
                if(($get_fare_estimation['distance']/1000) > $merchant->delivery_fee_base_distance){
                    $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance']/1000 - $merchant->delivery_fee_base_distance);
                }
                else{
                    $fee = $merchant->delivery_fee;
                }
            }
            $order->fee                     = round($fee, 2);

            $order->save();

            flashMessage('success', 'Order successfully added, Sending push messages to nearest drivers...');
            if($request->driver_id){
                $driver_details = User::where('id', $request->driver_id)->first();
                $message = "You are assigned to deliver order #" . $order->id . ". Please, check your deliveries.";
                $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message, true);
                try{
                    $trip_status = $this->schedule_delivery_trip($order, $driver_details);
                }
                catch(\Exception $e){
                    //
                }
            }
            else{
                $this->notify_drivers($request, 'New job(s) in your location', false);
            }

            flashMessage('success', 'Drivers notified');

            return redirect(LOGIN_USER_TYPE.'/home_delivery');
        }

        return redirect(LOGIN_USER_TYPE.'/home_delivery');
    }

    /**
     * Update Home Delivery Order Details
     *
     * @param array $request    Input values
     * @return redirect     to Home Delivery Order View
     */
    public function update(Request $request)
    {
        if($request->isMethod("GET")) {
            //Inactive Company could not add driver
            if (LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }

            $data['result'] = HomeDeliveryOrder::find($request->id);

            if($data['result']) {

                $user = User::where('id',$data['result']->customer_id)->first();

                $ride_request = RideRequest::where('id', $data['result']->ride_request)->first();

                $data['first_name'] = $user->first_name;

                $data['last_name'] = $user->last_name;

                $data['mobile_number'] = $user->mobile_number;

                $data['country_code_option'] = Country::select('long_name','phone_code')->get();

                $data['country_code'] = $user->country_code;

                $data['result']['pick_up_latitude'] = $ride_request->pickup_latitude;
                $data['result']['pick_up_longitude'] = $ride_request->pickup_longitude;
                $data['result']['pick_up_location'] = $ride_request->pickup_location;
                $data['result']['drop_off_latitude'] = $ride_request->drop_latitude;
                $data['result']['drop_off_longitude'] = $ride_request->drop_longitude;
                $data['result']['drop_off_location'] = $ride_request->drop_location;

                $timezone = date_default_timezone_get();

                $data['timezon'] = $timezone;

                if (LOGIN_USER_TYPE=='company' && session()->get('currency') != null) {
                    $default_currency = Currency::whereCode(session()->get('currency'))->first();
                }
                else {
                    $default_currency = view()->shared('default_currency');
                }
                $data['currency_symbol'] = html_string($default_currency->symbol);

                $data['estimate_time'] = $data['result']->created_at->addMinutes((int)$data['result']->estimate_time);

                return view('admin.delivery_order.edit', $data);
            }


            flashMessage('danger', 'Invalid ID');
            return redirect(LOGIN_USER_TYPE.'/home_delivery');
        }

        if($request->isMethod("POST")) {
            // Add Driver Validation Rules
            $rules = array(
                'estimate_time'     => 'required',
                'pick_up_location'  => 'required',
                'drop_off_location' => 'required',
                'customer_name'         => 'required',
                'customer_phone_number' => 'required',
                'pick_up_latitude'      => 'required',
                'pick_up_longitude'     => 'required',
                'drop_off_latitude'      => 'required',
                'drop_off_longitude'     => 'required',
                'merchant_id'            => 'required',
            );

            // Add Driver Validation Custom Names
            $attributes = array(
                'estimate_time'     => 'Estimate Time',
                'pick_up_location'  => 'Pick Up Location',
                'drop_off_location' => 'Drop Off Location',
                'customer_name'         => 'Customer Name',
                'customer_phone_number' => 'Customer Phone Number',
                'pick_up_latitude'      => 'Pick Up Latitude',
                'pick_up_longitude'     => 'Pick Up Longitude',
                'drop_off_latitude'      => 'Drop Off Latitude',
                'drop_off_longitude'     => 'Drop Off Longitude',
                'merchant_id'            => 'Merchant',
            );

            // Edit Rider Validation Custom Fields message
            $messages = array(
                'required'            => ':attribute is required.',
            );
            $validator = Validator::make($request->all(), $rules,$messages, $attributes);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $order = HomeDeliveryOrder::find($request->id);

            $ride_request = RideRequest::where('id', $order->ride_request)->first();

            $user = $this->get_or_create_rider($request);

            //calculate distance
            if(HERE_REST_KEY != ""){
                        $req = (object)array(
                            "origin_latitude" => $request->pick_up_latitude,
                            "origin_longitude" => $request->pick_up_longitude,
                            "destination_latitude" => $request->drop_off_latitude,
                            "destination_longitude" => $request->drop_off_longitude
                        );
                        $get_fare_estimation['distance'] = $this->map_service->getDistance($req);
            }
            else{
                $get_fare_estimation = $this->request_helper->GetDrivingDistance($request->pick_up_latitude, $request->drop_off_latitude,$request->pick_up_longitude, $request->drop_off_longitude);
                if ($get_fare_estimation['status'] == "success") {
                    if ($get_fare_estimation['distance'] == '') {
                        $get_fare_estimation['distance'] = 0;
                    }
                }
                else{
                    $get_fare_estimation['distance'] = 0;
                }
            }

            $order->distance                    = $get_fare_estimation['distance'];
            $order->estimate_time               = $request->estimate_time;
            $order->order_description           = $request->order_description;
            $ride_request->pickup_location      = $request->pick_up_location;
            $ride_request->drop_location        = $request->drop_off_location;
            $order->customer_id                 = $user->id;
            $ride_request->pickup_latitude      = $request->pick_up_latitude;
            $ride_request->pickup_longitude     = $request->pick_up_longitude;
            $ride_request->drop_latitude        = $request->drop_off_latitude;
            $ride_request->drop_longitude       = $request->drop_off_longitude;

            //Check current assigned driver for notification
            $current_driver = null;
            $current_trip = null;
            if($order->driver_id){
                $current_driver = $order->driver_id;
                $current_trip =  Trips::where('request_id', $ride_request->id)->where('driver_id', $current_driver)->whereNotIn('status', ['Cancelled'])->first();
            }

            if($current_driver != $request->driver_id){
                if($request->driver_id == null){
                    $order->driver_id = null;
                    $ride_request->driver_id = 1;
                    $order->status = 'new';
                }
                else{
                    $order->driver_id           = $request->driver_id;
                    $ride_request->driver_id    = $request->driver_id;
                    $order->status = 'assigned';
                }

                if($current_trip){
                    $current_trip->status = 'Cancelled';
                    $current_trip->save();
                }
            }
            $ride_request->save();

            $order->merchant_id             = $request->merchant_id;

            $merchant = Merchant::where('id', $request->merchant_id)->first();
            $fee = 0.0;
            if($request->fee){
                $fee = (float)$request->fee;
            }
            else{
                if(($get_fare_estimation['distance']/1000) > $merchant->delivery_fee_base_distance){
                    $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance']/1000 - $merchant->delivery_fee_base_distance);
                }
                else{
                    $fee = $merchant->delivery_fee;
                }
            }
            $order->fee                     = round($fee, 2);

            $order->save();

            //send notification to new assigned driver
            try{
                if($current_driver != $request->driver_id){
                    if($current_driver != null){
                        $driver_details = User::where('id', $current_driver)->first();
                        $message = "Your order #" . $order->id . "was re-assigned.";
                        $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message, true);
                    }
                    if($request->driver_id){
                        $driver_details = User::where('id', $request->driver_id)->first();
                        $message = "You are assigned to deliver order #" . $order->id . ". Please, check your deliveries.";
                        $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message, true);
                        $driver = User::find($request->driver_id);
                        try{
                            $trip_status = $this->schedule_delivery_trip($order, $driver);
                        }
                        catch(\Exception $e){
                            //
                        }
                    }
                    if($order->status == 'new'){
                        $this->notify_drivers($request, 'New job(s) in your location', false);
                    }
                }
            }
            catch(\Exception $e) {
                //
            }

            flashMessage('success', 'Order successfully updated');

            return redirect(LOGIN_USER_TYPE.'/home_delivery');
        }

        return redirect(LOGIN_USER_TYPE.'/home_delivery');
    }

    /**
     * Delete Order
     *
     * @param array $request    Input values
     * @return redirect     to Order View
     */
    public function delete(Request $request)
    {
        $result= $this->canDestroy($request->id);

        if($result['status'] == 0) {
            flashMessage('error',$result['message']);
            return back();
        }

        try {

            $order = HomeDeliveryOrder::find($request->id);
            $rr = RideRequest::where('id',$order->ride_request)->first();
            $tr = Trips::where('request_id',$rr->id)->get();
            foreach($tr as $trip){
                $trip->status = 'Cancelled';
                $trip->save();
                $trip->delete();
            }
            $rr->delete();
            $order->delete();
        }
        catch(\Exception $e) {
            flashMessage('error','Got a problem on deleting this order. Contact admin, please');
            return back();
        }

        flashMessage('success', 'Deleted Successfully');
        return redirect(LOGIN_USER_TYPE.'/home_delivery');
    }


    /**
     * Suspend Bad Orders
     *
     * @param array $request    Input values
     * @return redirect     to Order View
     */
    public function suspend(Request $request)
    {
        try {
            $order = HomeDeliveryOrder::find($request->id);
            if($order->status != 'delivered'){
                flashMessage('error', 'You cannot disapprove an undelivered order');
                return back();
            }
            else{
                $ride_request = RideRequest::where('id',$order->ride_request)->first();
                $trip = Trips::where('request_id',$ride_request->id)->first();
                $payment = Payment::where('trip_id', $trip->id)->first();
                if($payment->driver_payout_status != 'Suspended'){
                    $payment->driver_payout_status = 'Suspended';

                    $driver_details = User::where('id', $order->driver_id)->first();

                    $email = $driver_details->email;

                    $content    = [
                        'first_name' => $driver_details->first_name
                    ];
                    $data['first_name'] = $content['first_name'];
                    $data['order_number'] = $order->id;
                    $data['order_date'] = $order->created_at;
                    $data['pick_up'] = $ride_request->pickup_location;
                    $data['drop_off'] = $ride_request->drop_location;
                    $data['fee'] = $trip->driver_payout;
                    $data['reason'] = $request->reason ? $request->reason : 'No reason';

                    //Send push
                    if($driver_details->device_id != "" && $driver_details->status == "Active")
                    {
                        $message = "Your funds related to delivery order #" . $data['order_number'] . "were withheld. Please, contact us.";
                        $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message, false);
                    }

                    // Send Forgot password email to give user email
                    Mail::send('emails.payouts_suspent', $data, function($message) use ($email, $content){
                        $message->to($email, $content['first_name'])->subject('Your funds were withheld.');
                        $message->from('api@rideon.group','Ride on Tech support');
                    });

                    $payment->save();

                    flashMessage('success', ' Payments for order #'. $order->id .' successfully disapproved');
                    return redirect(LOGIN_USER_TYPE.'/home_delivery');
                }
                else{
                    flashMessage('warning', 'Payments for order #'. $order->id . ' are already disapproved');
                    return redirect(LOGIN_USER_TYPE.'/home_delivery');
                }

            }
        }
        catch(\Exception $e) {
            flashMessage('error','Got a problem on suspending this order. Contact admin, please' . $e->getMessage());
            return back();
        }

    }

        /**
     * Resume Bad Orders payment
     *
     * @param array $request    Input values
     * @return redirect     to Order View
     */
    public function resume(Request $request)
    {
        try {
            $order = HomeDeliveryOrder::find($request->id);
            if($order->status != 'delivered'){
                flashMessage('error', 'You cannot approve an undelivered order');
                return back();
            }
            else{
                $ride_request = RideRequest::where('id',$order->ride_request)->first();
                $trip = Trips::where('request_id',$ride_request->id)->first();
                $payment = Payment::where('trip_id', $trip->id)->first();
                if($payment->driver_payout_status == 'Suspended'){
                    $payment->driver_payout_status = 'Pending';
                    $payment->save();
                    flashMessage('success', 'Payments for order #'. $order->id .' successfully approved');
                    return redirect(LOGIN_USER_TYPE.'/home_delivery');
                }
                else{
                    flashMessage('warning', 'Payments for order #'. $order->id . ' are already approved');
                    return redirect(LOGIN_USER_TYPE.'/home_delivery');
                }
            }
        }
        catch(\Exception $e) {
            flashMessage('error','Got a problem on deleting this order. Contact admin, please');
            return back();
        }

    }

    // Check Given Order deletable or not
    public function canDestroy($order_id)
    {
        $return  = array('status' => '1', 'message' => '');

        // $driver_trips   = Trips::where('driver_id',$user_id)->count();
        // $user_referral  = ReferralUser::where('user_id',$user_id)->orWhere('referral_id',$user_id)->count();

        // if($driver_trips) {
        //     $return = ['status' => 0, 'message' => 'Driver have some trips, So can\'t delete this driver'];
        // }
        // else if($user_referral) {
        //     $return = ['status' => 0, 'message' => 'Rider have referrals, So can\'t delete this driver'];
        // }
        return $return;
    }

    /**
     * custom push notification
     *
     * @return success or fail
     */
    public function send_custom_pushnotification($device_id,$device_type,$user_type,$message,$isAssigned)
    {
        if (LOGIN_USER_TYPE=='company') {
            $push_title = "Message from ".Auth::guard('company')->user()->name;
        }
        else {
            $push_title = "Message from ".SITE_NAME;
        }

        if(!isset($isAssigned)){
            $isAssigned = false;
        }

        try {
            if($device_type == 1) {
                $data       = array('custom_message' => array('title' => $message,'push_title'=>$push_title, "isAssigned" => $isAssigned));
                $this->request_helper->push_notification_ios($message, $data, $user_type, $device_id,$admin_msg=1);
            }
            else {
                $data       = array('custom_message' => array('message_data' => $message,'title' => $push_title, "isAssigned" => $isAssigned ));
                $this->request_helper->push_notification_android($push_title, $data, $user_type, $device_id,$admin_msg=1);
            }
        }
        catch (\Exception $e) {
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
            ->where('user_type','Rider')->first();

        if(!$user){
            $user = new User;
            $user->mobile_number    =   $request->mobile_number;
            $user->first_name       =   $request->first_name;
            $user->last_name        =   $request->last_name;
            $user->user_type        =   'Rider';
            $user->password         =   $request->password;
            $user->country_code     =   $request->country_code;
            $user->language         =   $language;
            $user->email            =   $request->mobile_number . '@rideon.group';
            $user->currency_code    =   get_currency_from_ip();

            $user->save();
        }
        return $user;
    }

    /**
     * Notify nearest drivers
     *
     * @return success or fail
     */
    public function notify_drivers($request, $message, $isAssigned)
    {
        if(!isset($isAssigned)){
            $isAssigned = false;
        }
        $nearest_cars = DriverLocation::select(DB::raw('*, ( 6371 * acos( cos( radians(' . $request->pick_up_latitude . ') ) * cos( radians( latitude ) ) * cos(radians( longitude ) - radians(' . $request->pick_up_longitude . ') ) + sin( radians(' . $request->pick_up_latitude . ') ) * sin( radians( latitude ) ) ) ) as distance'))
            ->having('distance', '<=', 15)->get();

            foreach ($nearest_cars as $nearest_car) {
                $driver_details = User::where('id', $nearest_car->user_id)->first();

                if($driver_details->device_id != "" && $driver_details->status == "Active")
                {
                    $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message, $isAssigned);
                }
            }
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

    public function home_delivery_order_details(Request $request){

        // This is for getting Order Result
        $data['order_result'] = HomeDeliveryOrder::find($request->id);

        $data['order_result'] ->merchant_name = Merchant::where('id', $data['order_result']['merchant_id'])->get()->first()->name;
        $estimate_time = $data['order_result']['estimate_time'];
        $delivery_time = new DateTime($data['order_result']['created_at']);
        $delivery_time->add(new DateInterval('PT' . $estimate_time . 'M'));
        $delivery_time_stamp = $delivery_time->format('Y-m-d H:i:s');
        $data['order_result'] ->delivery_time =$delivery_time_stamp;
        if(now()>$delivery_time_stamp){
            $data['order_result'] -> delivery_current_status = true;
        }
        else{
            $data['order_result'] -> delivery_current_status = false;
        }

        // This is for getting Location Result
        $data['location_result'] = RideRequest::where('id', $data['order_result']->ride_request)->get()->first();
        $data['location_result'] ->distance = $data['order_result']->distance/1000;
        $trip = Trips::where('request_id', $data['location_result']->id)
                ->where('driver_id',  $data['order_result']->driver_id)
                ->where('status', '!=', 'Cancelled')
                ->first();

        //dd($request);

        $data['real_location_result'] = array(
            'real_drop_location' => 'None',
            'real_drop_latitude' => 'None',
            'real_drop_longitude' => 'None',
            'real_pickup_location' => 'None',
            'real_pickup_latitude' => 'None',
            'real_pickup_longitude' => 'None',
            'real_pickup_time' => 'None',
            'real_drop_time' => 'None',
        );
        if($trip){
            $data['real_location_result']['real_pickup_location'] = $trip->pickup_location;
            $data['real_location_result']['real_pickup_latitude'] = $trip->pickup_latitude;
            $data['real_location_result']['real_pickup_longitude'] = $trip->pickup_longitude;
            $data['real_location_result']['real_pickup_time'] = $trip->begin_trip;
            $data['real_location_result']['real_drop_location'] = $trip->drop_location;
            $data['real_location_result']['real_drop_latitude'] = $trip->drop_latitude;
            $data['real_location_result']['real_drop_longitude'] = $trip->drop_longitude;
            $data['real_location_result']['real_drop_time'] = $trip->end_trip;
        }

        //This is for getting Customer Data
        $data['customer'] = User::where('id',$data['order_result']->customer_id)->first();
        $data['customer']->country = Country::where('phone_code',$data['customer'] ->country_code)->get()->first()->long_name;

        //Getting Drivers data
        $data['driver'] = null;

        if($data['order_result']->driver_id){

            $data['driver'] = DB::table('users')
                ->leftJoin('driver_location', 'users.id', '=','driver_location.user_id')
                ->select([
                    'users.id AS driver_id',
                    'users.first_name AS first_name',
                    'users.last_name AS last_name',
                    DB::raw('CONCAT("+", users.country_code, users.mobile_number) AS mobile_number'),
                    'driver_location.latitude as driver_latitude',
                    'driver_location.longitude as driver_longitude',
                ])
                ->where('users.id',$data['order_result']->driver_id)
                ->first();
        }
        return view('admin.delivery_order.order_details', $data);
    }

     /**
     * Shcedule delivery trip
     *
     * @return success or fail
     */
    public function schedule_delivery_trip($order, $driver)
    {
            $rider = User::where('id', $order->customer_id)->first();
            $ride_request = RideRequest::where('id',$order->ride_request)->first();

            //Insert record in Trips table
            $trip = Trips::where('request_id', $ride_request->id)->where('status', '!=', 'Cancelled')->first();
            if(!$trip){
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

                $subscription = DriversSubscriptions::where('user_id',$driver->id)
                    ->whereNotIn('status', ['canceled'])
                    ->first();

                $plan = StripeSubscriptionsPlans::where('id',$subscription->plan)->first();

                $tax_gst = 1 + site_settings('tax_gst') / 100; //tax or gst
                if ($plan->plan_name == 'Driver Only'){
                    $commission_percent = site_settings('regular_driver_booking_fee') / 100;
                    $commission = $order->fee * $commission_percent;
                    $trip->driver_or_company_commission = $commission;
                    $trip->driver_payout = ($order->fee - $commission) * $tax_gst;
                    $trip->total_fare = $order->fee * $tax_gst;
                }
                else {
                    $trip->driver_or_company_commission = 0.00;
                    $trip->driver_payout = $order->fee * $tax_gst;
                    $trip->total_fare = $order->fee  * $tax_gst;
                }
                $trip->save();
            }

            return array([
                    'status_code' 		=> '1',
                    'status_message' 	=> 'Trip schedulled.',
            ]);
    }

}
