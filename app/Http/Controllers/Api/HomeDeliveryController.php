<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\RequestHelper;
use App\Http\Start\Helpers;
use App\Models\DriverLocation;
use App\Models\DriversSubscriptions;
use App\Models\HomeDeliveryOrder;
use App\Models\Merchant;
use App\Models\Payment;
use App\Models\Request as RideRequest;
use App\Models\StripeSubscriptionsPlans;
use App\Models\Trips;
use App\Models\User;
use App\Models\Vehicle;
use App\Http\Controllers\TrackingController;
use DateTime;
use DB;
use Illuminate\Http\Request;
use JWTAuth;
use Validator;

use Carbon\Carbon;

class HomeDeliveryController extends Controller
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
     * Get orders data
     *
     * @param  Get method request inputs
     *
     * @return Response Json
     */
    public function getOrders(Request $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $rules = array(
            'distance' => 'required|in:5,10,15',
            'latitude' => 'required',
            'longitude' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first(),
            ]);
        }
        $user = User::where('id', $user_details->id)->first();

        if ($user == '') {
            return response()->json([
                'status_code' => '0',
                'status_message' => "Invalid credentials",
            ]);
        }

        $job_array = array();
        $distances = array("5", "10", "15");
        if (in_array($request->distance, $distances)) {
            $job_array = $this->get_jobs_list($request, $user_details);
        } else {
            return response()->json([
                'status_code' => '0',
                'status_message' => "Wrong distance",
            ]);
        }

        return response()->json([
            'status_code' => '1',
            'status_message' => "Success",
            'jobs' => $job_array,
        ]);
    }

    /**
     * Get orders data
     *
     * @param  Get method request inputs
     *
     * @return Response Json
     */
    public function getDriverOrders(Request $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $user = User::where('id', $user_details->id)->first();

        if ($user == '') {
            return response()->json([
                'status_code' => '0',
                'status_message' => "Invalid credentials",
            ]);
        }

        $job_array = $this->get_my_jobs_list($request, $user_details);

        return response()->json([
            'status_code' => '1',
            'status_message' => "Success",
            'jobs' => $job_array,
        ]);
    }

    /**
     * Accept orders
     *
     * @param  Get method request inputs
     *
     * @return Response Json
     */
    public function acceptOrder(Request $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $rules = array(
            'order_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'distance' => 'required|in:5,10,15',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first(),
            ]);
        }
        $user = User::where('id', $user_details->id)->first();

        if ($user == '') {
            return response()->json([
                'status_code' => '0',
                'status_message' => "Invalid credentials",
            ]);
        }

        $order = HomeDeliveryOrder::where('id', $request->order_id)->first();
        $assign_status_message = '';
        $order_status = $order->status;

       try{
           $driver_orders_count = DB::table('delivery_orders')->where('driver_id', $user->id)->where('deleted_at', null)->whereNotIn('status',['delivered'])->count();
           $max_orders_value = (int)site_settings('max_self_assign_orders');
           if ($max_orders_value > 0 && $driver_orders_count >= $max_orders_value) {
                return response()->json([
                    'status_code' => '0',
                    'status_message' => 'Sorry, you cannot receive more orders until you deliver the current one.',
                ]);
           }
       }
       catch (\Exception $e){
           logger($e->getMessage());
       }

        if ($order_status == 'new') {
            $subscription = DriversSubscriptions::where('user_id', $user->id)
                ->whereNotIn('status', ['canceled'])
                ->first();
            if (!$subscription) {
                return response()->json([
                    'status_code' => '0',
                    'status_message' => 'Sorry, you have no subscription for this action.',
                ]);
            } else {
                $order->status = 'assigned';
                $order->driver_id = $user->id;
                $order->save();
                
                try{
                    $ride_request = RideRequest::find($order->ride_request);
                    $ride_request->driver_id = $user->id;
                    $ride_request->save();
                    $trip_status = $this->schedule_delivery_trip($order, $user);
                } catch (\Exception $e) {
                    //
                }
                $assign_status_message = 'successfully assigned';
            }
            //Generate and store tracking Id.
            $tracking_id = (new TrackingController)->storeTrackingId($order->id);

            if($order->merchant_id == 12){
                try {
                    $merchant_contact = DB::table('merchants')->where('merchants.id', $order->merchant_id)->leftJoin('users', 'merchants.user_id', '=', 'users.id')->first();
                    $merchant_phone_number = "+" . $merchant_contact->country_code . $merchant_contact->mobile_number;
                    $customer = User::where('id', $order->customer_id)->first();

                    $message_text = "Your driver for " . $customer->first_name . " " . $customer->last_name . " is on their way. ";

                    $driver_loc = DriverLocation::where('user_id', $order->driver_id)->first();
                    $request_loc = RideRequest::where('id', $order->ride_request)->first();

                    $time = '';
                    if(HERE_REST_KEY != ""){
                        $req = (object)array(
                            "origin_latitude" => $request_loc->pickup_latitude, 
                            "origin_longitude" => $request_loc->pickup_longitude, 
                            "destination_latitude" => $driver_loc->latitude, 
                            "destination_longitude" => $driver_loc->longitude
                        );
                        $get_fare_estimation['time'] = $this->map_service->getETA($req);
                    }
                    else{
                        $get_fare_estimation = $this->request_helper->GetDrivingDistance($request_loc->pickup_latitude, $driver_loc->latitude, $request_loc->pickup_longitude, $driver_loc->longitude);
                        if ($get_fare_estimation['status'] == "success") {
                            if ($get_fare_estimation['time'] == '') {
                                $get_fare_estimation['time'] = null;
                            }
                            else{
                                $get_fare_estimation['time'] = $get_fare_estimation['time'] / 60;
                            }
                        }
                        else{
                            $get_fare_estimation['time'] = null;
                        }
                    }

                    $time = (int) ($get_fare_estimation['time']) . ' minutes';

                    if ($time) {
                        $message_text = $message_text . " ETA " . $time . '. Track it on https://tracking.rideon.co/tracking/' . $tracking_id;
                    }

                    $sms_result = $this->request_helper->send_message($merchant_phone_number, $message_text);
                    $whatsapp_result = $this->request_helper->send_whatsapp_message($merchant_phone_number, $message_text);
                } catch (\Exception $e) {
                    //
                }
            }

            $job_array = $this->get_jobs_list($request, $user_details);

            return response()->json([
                'status_code' => '1',
                'status_message' => "Order with id " . $order->id . " " . $assign_status_message,
                'jobs' => $job_array,
            ]);
        } else {
            return response()->json([
                'status_code' => '0',
                'status_message' => 'Order already assigned.',
            ]);
        }
    }

    /**
     * Proceed orders
     *
     * @param  Get method request inputs
     *
     * @return Response Json
     */
    public function proceedOrder(Request $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $rules = array(
            'order_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first(),
            ]);
        }
        $user = User::where('id', $user_details->id)->first();

        if ($user == '') {
            return response()->json([
                'status_code' => '0',
                'status_message' => "Invalid credentials",
            ]);
        }

        $order = HomeDeliveryOrder::where('id', $request->order_id)->first();

        $assign_status_message = '';

        $order_status = $order->status;

        if (($request->cancel == "True" || $request->cancel == true) && $order_status != 'new' && $order_status != 'delivered') {
            $order->status = $order_status = 'new';
            $order->driver_id = null;

            $order->save();

            try {
                $this->cancel_delivery_trip($order, $user);

                $ride_request = RideRequest::where('id', $order->ride_request)->first();

                $this->notify_drivers($ride_request, 'New job(s) in your location');
            } catch (\Exception $e) {
                //
            }
            if($order->merchant_id == 12){
                try {
                    $merchant_contact = Merchant::where('merchants.id', $order->merchant_id)->leftJoin('users', 'merchants.user_id', '=', 'users.id')->first();
                    $merchant_phone_number = "+" . $merchant_contact->country_code . $merchant_contact->mobile_number;
                    $customer = User::where('id', $order->customer_id)->first();

                    $message_text = "We are finding you another driver for " . $customer->first_name . " " . $customer->last_name . " order. Pls stand by";

                    $sms_result = $this->request_helper->send_message($merchant_phone_number, $message_text);
                    $whatsapp_result = $this->request_helper->send_whatsapp_message($merchant_phone_number, $message_text);
                } catch (\Exception $e) {
                    //
                }
            }

            $assign_status_message = 'successfully cancelled';
        } elseif ($order_status == 'assigned') {
            #assigned -> picked_up
            if ($order->driver_id != $user->id) {
                return response()->json([
                    'status_code' => '0',
                    'status_message' => 'Order already assigned.',
                ]);
            }

            $order->status = $order_status = 'picked_up';

            $order->save();

            try {
                $trip_status = $this->start_delivery_trip($request, $order, $user);

                $merchant_contact = Merchant::where('id', $order->merchant_id)->first();

                $customer = User::where('id', $order->customer_id)->first();
                $customer_phone_number = "+" . $customer->country_code . $customer->mobile_number;

                if ($merchant_contact->name == 'Default Merchant') {
                    $message_text = "Your food has been picked up and on its way.";
                } else {
                    $message_text = "Your food from " . $merchant_contact->name . " has been picked up and on its way.";
                }

                $driver_loc = DriverLocation::where('user_id', $order->driver_id)->first();
                $request_loc = RideRequest::where('id', $order->ride_request)->first();

                $time = '';

                $time = '';
                if(HERE_REST_KEY != ""){
                    $req = (object)array(
                        "origin_latitude" => $request_loc->drop_latitude, 
                        "origin_longitude" => $request_loc->drop_longitude, 
                        "destination_latitude" => $driver_loc->latitude, 
                        "destination_longitude" => $driver_loc->longitude
                    );
                    $get_fare_estimation['time'] = $this->map_service->getETA($req);
                }
                else{
                    $get_fare_estimation = $this->request_helper->GetDrivingDistance($request_loc->drop_latitude, $driver_loc->latitude, $request_loc->drop_longitude, $driver_loc->longitude);
                    if ($get_fare_estimation['status'] == "success") {
                        if ($get_fare_estimation['time'] == '') {
                            $get_fare_estimation['time'] = null;
                        }
                        else{
                            $get_fare_estimation['time'] = $get_fare_estimation['time'] / 60;
                        }
                    }
                    else{
                        $get_fare_estimation['time'] = null;
                    }
                }

                $time = (int) ($get_fare_estimation['time']) . ' minutes';

                if ($time) {
                    $message_text = $message_text . " ETA " . $time . '. Track it on https://tracking.rideon.co/tracking/' . $order->tracking_link;
                }

                $sms_result = $this->request_helper->send_message($customer_phone_number, $message_text);
                $whatsapp_result = $this->request_helper->send_whatsapp_message($customer_phone_number, $message_text);
            } catch (\Exception $e) {
                //
            }

            $assign_status_message = 'successfully picked up';
        } elseif ($order_status == 'picked_up') {
            try {
                $trip_status = $this->end_delivery_trip($request, $order, $user);
            } catch (\Exception $e) {
                //
            }
            $order->status = $order_status = 'delivered';
            $order->delivered_at = \Illuminate\Support\Carbon::now()->toDateString();

            $order->save();

            $assign_status_message = 'successfully delivered';
            if($order->merchant_id == 12){
                try {
                    $merchant_contact = Merchant::where('merchants.id', $order->merchant_id)->leftJoin('users', 'merchants.user_id', '=', 'users.id')->first();
                    $merchant_phone_number = "+" . $merchant_contact->country_code . $merchant_contact->mobile_number;
                    $customer = User::where('id', $order->customer_id)->first();

                    $message_text = "Your order for " . $customer->first_name . " " . $customer->last_name . " has been dropped off";

                    $sms_result = $this->request_helper->send_message($merchant_phone_number, $message_text);
                    $whatsapp_result = $this->request_helper->send_whatsapp_message($merchant_phone_number, $message_text);
                } catch (\Exception $e) {
                    //
                }
            }

        } elseif ($order_status == 'delivered') {
            return response()->json([
                'status_code' => '0',
                'status_message' => 'Order already delivered.',
            ]);
        } else {
            return response()->json([
                'status_code' => '0',
                'status_message' => 'Wrong order transition.',
            ]);
        }

        return response()->json([
            'status_code' => '1',
            'status_message' => "Order with id " . $order->id . ' ' . $assign_status_message,
            'job_status' => $order_status,
        ]);
    }

    /**
     * Get new jobs list
     *
     */
    public function get_jobs_list($request, $user_details)
    {
        $job_array = array();
        $dst = (int) $request->distance;

        $driver_location = DriverLocation::where('user_id', $user_details->id)->first();

        $data = [
            'user_id' => $user_details->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        $vehicle = Vehicle::where('user_id', $user_details->id)->first();

        if ($vehicle) {
            $data['car_id'] = $vehicle->vehicle_id;
        } else {
            $data['car_id'] = '1';
        }

        if (!$driver_location) {
            $data['status'] = "Online";
        }

        DriverLocation::updateOrCreate(['user_id' => $user_details->id], $data);

        $driver_location = DriverLocation::where('user_id', $user_details->id)->first();

        $orders = DB::table('delivery_orders')->whereIn('delivery_orders.status', ['new', 'expired'])
            ->join('users as rider', function ($join) {
                $join->on('rider.id', '=', 'delivery_orders.customer_id');
            })
            ->join('request as ride_request', function ($join) {
                $join->on('ride_request.id', '=', 'delivery_orders.ride_request');
            })
            ->join('merchants as merchant', function ($join) {
                $join->on('merchant.id', '=', 'delivery_orders.merchant_id');
            })
            ->select(
                DB::raw('*, ( 6371 * acos( cos( radians(ride_request.pickup_latitude) ) * cos( radians( ' . $driver_location->latitude . ' ) ) * cos(radians( ' . $driver_location->longitude . ' ) - radians(ride_request.pickup_longitude) ) + sin( radians(ride_request.pickup_latitude) ) * sin( radians( ' . $driver_location->latitude . ' ) ) ) ) as distance'),
                'delivery_orders.id as id',
                'delivery_orders.driver_id as driver_id',
                'delivery_orders.created_at as created_at',
                'delivery_orders.estimate_time as estimate_time',
                'delivery_orders.fee as fee',
                'delivery_orders.distance as delivery_distance',
                'delivery_orders.status as status',
                'delivery_orders.currency_code as currency_code',
                'ride_request.pickup_location as pick_up_location',
                'ride_request.drop_location as drop_off_location',
                'merchant.name as merchant_name',
                DB::raw('CONCAT(rider.first_name," ",rider.last_name) as customer_name'),
                DB::raw('CONCAT("+",rider.country_code,rider.mobile_number) as customer_phone_number'),
                DB::raw('TIMEDIFF(NOW(),(date_add(delivery_orders.created_at,interval delivery_orders.estimate_time minute))) as time_to_dead'),
            )
            ->having('distance', '<=', $dst)
            ->where('delivery_orders.deleted_at',null)
            ->whereIn('delivery_orders.status', ['new', 'expired'])
            ->orWhere('delivery_orders.driver_id', $user_details->id)
            ->whereNotIn('delivery_orders.status', ['delivered', 'assigned', 'picked_up'])
            ->orderBy('time_to_dead', 'desc')->get();

        foreach ($orders as $order) {
            $temp_details = array();
            $date_now = \Illuminate\Support\Carbon::now();
            $date_estimate = Carbon::createFromTimeString($order->created_at)->addMinutes($order->estimate_time);
            $date_diff = $date_now->diffInMinutes($date_estimate, false);
            try{
                $timezone = $this->request_helper->getTimeZone($driver_location->latitude, $driver_location->longitude);
                $date = Carbon::createFromTimeString($order->created_at)->setTimezone($timezone);
                $date_estimate = Carbon::createFromTimeString($order->created_at)->addMinutes($order->estimate_time)->setTimezone($timezone);
                $temp_details['estimate_time'] = $date_estimate->format('g:i A');
            } catch (\Exception $e) {
                $date = new DateTime($order->created_at);
                $temp_details['estimate_time'] = $date_diff . ' Min';
            }
            $temp_details['status'] = $order->status;
            if ($date_diff < 0 && $order->status != 'assigned') {
                $temp_details['estimate_time'] = 'Expired';

            }
            if ($order->status == 'expired') {
                $temp_details['status'] = 'new';
            }
            $temp_details['order_id'] = $order->id;
            $temp_details['merchant'] = $order->merchant_name;
            $temp_details['date'] = $date->format('d M Y | H:i');
            $temp_details['pick_up_location'] = $order->pick_up_location;
            $temp_details['pick_up'] = $order->merchant_name;
            $temp_details['drop_off'] = $order->drop_off_location;
            if ($order->status == 'assigned') {
                $temp_details['customer_name'] = $order->customer_name;
                $temp_details['customer_phone_number'] = $order->customer_phone_number;
            }
            $temp_details['distance'] = (string) round((float) $order->delivery_distance / 1000, 2) . 'KM';
            $temp_details['fee'] = '$' . (round( ($order->fee * 1.1), 2 ));
            array_push($job_array, $temp_details);
        }

        return $job_array;
    }

    /**
     * Get driver jobs list
     *
     */
    public function get_my_jobs_list($request, $user_details)
    {
        $job_array = array();

        $orders = DB::table('delivery_orders')->whereIn('delivery_orders.status', ['assigned', 'picked_up'])
            ->join('users as rider', function ($join) {
                $join->on('rider.id', '=', 'delivery_orders.customer_id');
            })
            ->join('request as ride_request', function ($join) {
                $join->on('ride_request.id', '=', 'delivery_orders.ride_request');
            })
            ->join('merchants as merchant', function ($join) {
                $join->on('merchant.id', '=', 'delivery_orders.merchant_id');
            })
            ->select(
                'delivery_orders.id as id',
                'delivery_orders.driver_id as driver_id',
                'delivery_orders.created_at as created_at',
                'delivery_orders.estimate_time as estimate_time',
                'delivery_orders.order_description as order_description',
                'delivery_orders.fee as fee',
                'delivery_orders.distance as delivery_distance',
                'delivery_orders.status as status',
                'delivery_orders.currency_code as currency_code',
                'ride_request.pickup_latitude as pickup_latitude',
                'ride_request.pickup_longitude as pickup_longitude',
                'ride_request.drop_latitude as drop_latitude',
                'ride_request.drop_longitude as drop_longitude',
                'ride_request.pickup_location as pick_up_location',
                'ride_request.drop_location as drop_off_location',
                'merchant.name as merchant_name',
                DB::raw('CONCAT(rider.first_name," ",rider.last_name) as customer_name'),
                DB::raw('CONCAT("+",rider.country_code,rider.mobile_number) as customer_phone_number'),
                DB::raw('TIMEDIFF(NOW(),(date_add(delivery_orders.created_at,interval delivery_orders.estimate_time minute))) as time_to_dead'),
            )
            ->where('delivery_orders.driver_id', $user_details->id)
            ->where('delivery_orders.deleted_at',null)
            ->whereNotIn('delivery_orders.status', ['delivered', 'new', 'expired'])
            ->orderBy('time_to_dead', 'desc')->get();

        foreach ($orders as $order) {
            $temp_details = array();

            $date_now = \Illuminate\Support\Carbon::now();
            $date_estimate = Carbon::createFromTimeString($order->created_at)->addMinutes($order->estimate_time);
            $date_diff = $date_now->diffInMinutes($date_estimate, false);
            // $time1 = (int)($date_diff/60);
            // $time2 = $date_diff%60;
            // $temp_details['estimate_time'] =  $time1 . '.' . $time2 . ' Hours';
            try {
                $driver_location = DriverLocation::where('user_id', $user_details->id)->first();
                $timezone = $this->request_helper->getTimeZone($driver_location->latitude, $driver_location->longitude);
                $date = Carbon::createFromTimeString($order->created_at)->setTimezone($timezone);
                $date_estimate = Carbon::createFromTimeString($order->created_at)->addMinutes($order->estimate_time)->setTimezone($timezone);
                $temp_details['estimate_time'] = $date_estimate->format('g:i A');
            } catch (\Exception $e) {
                $date = new DateTime($order->created_at);
                $temp_details['estimate_time'] = $date_diff . ' Min';
            }
            $temp_details['status'] = $order->status;

            if ($date_diff < 0) {
                $temp_details['estimate_time'] = 'Expired';

            }

            $temp_details['order_id'] = $order->id;
            $temp_details['date'] = $date->format('d M Y | H:i');
            $temp_details['pick_up_time'] = $date_estimate->format('H:i A');
            $temp_details['pick_up'] = $order->pick_up_location;
            $temp_details['pickup_latitude'] = floatval($order->pickup_latitude);
            $temp_details['pickup_longitude'] = floatval($order->pickup_longitude);
            $temp_details['drop_longitude'] = floatval($order->drop_longitude);
            $temp_details['drop_latitude'] = floatval($order->drop_latitude);
            $temp_details['merchant'] = $order->merchant_name;
            $temp_details['drop_off'] = $order->drop_off_location;
            $temp_details['customer_name'] = $order->customer_name;
            $temp_details['customer_phone_number'] = $order->customer_phone_number;
            $temp_details['order_description'] = $order->order_description ? $order->order_description : 'No description provided';
            $temp_details['fee'] = '$' . (round( ($order->fee * 1.1), 2 ));

            array_push($job_array, $temp_details);
        }

        return $job_array;
    }

    /**
     * Notify nearest drivers
     *
     * @return success or fail
     */
    public function notify_drivers($request, $message)
    {
        $nearest_cars = DriverLocation::select(DB::raw('*, ( 6371 * acos( cos( radians(' . $request->pickup_latitude . ') ) * cos( radians( latitude ) ) * cos(radians( longitude ) - radians(' . $request->pickup_longitude . ') ) + sin( radians(' . $request->pickup_latitude . ') ) * sin( radians( latitude ) ) ) ) as distance'))
            ->having('distance', '<=', 15)->get();

        foreach ($nearest_cars as $nearest_car) {
            $driver_details = User::where('id', $nearest_car->user_id)->first();

            if ($driver_details->device_id != "" && $driver_details->status == "Active") {
                $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message);
            }
        }
    }

    /**
     * custom push notification
     *
     * @return success or fail
     */
    public function send_custom_pushnotification($device_id, $device_type, $user_type, $message)
    {
        if (LOGIN_USER_TYPE == 'company') {
            $push_title = "Message from " . Auth::guard('company')->user()->name;
        } else {
            $push_title = "Message from " . SITE_NAME;
        }

        try {
            if ($device_type == 1) {
                $data = array('custom_message' => array('title' => $message, 'push_title' => $push_title));
                $this->request_helper->push_notification_ios($message, $data, $user_type, $device_id, $admin_msg = 1);
            } else {
                $data = array('custom_message' => array('message_data' => $message, 'title' => $push_title));
                $this->request_helper->push_notification_android($push_title, $data, $user_type, $device_id, $admin_msg = 1);
            }
        } catch (\Exception $e) {
            logger('Could not send push notification');
        }
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
        }
        else {
            $trip->driver_or_company_commission = 0.00;
            $trip->driver_payout = $order->fee * $tax_gst;
            $trip->total_fare = $order->fee  * $tax_gst;
        }

            $trip->save();
            $curr_trip = $trip;

            return array([ 
                    'status_code' 		=> '1',
                    'status_message' 	=> 'Trip schedulled.',
                    'trip_id'           => $curr_trip->id,
            ]);
    }

    /**
     * Cancel delivery trip
     *
     * @return success or fail
     */
    public function cancel_delivery_trip($order, $driver)
    {
        $ride_request = RideRequest::where('id', $order->ride_request)->first();

        $trip = Trips::where('request_id', $ride_request->id)
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'Cancelled')
            ->first();

        if ($trip) {
            $trip->status = 'Cancelled';
            $trip->payment_status = 'Trip Cancelled';
            $trip->end_trip = \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s');
            $trip->save();
        }

        return array([
            'status_code' => '1',
            'status_message' => 'Trip cancelled.',
        ]);
    }

    /**
     * Start delivery trip (pick up)
     *
     * @return success or fail
     */
    public function start_delivery_trip($request, $order, $driver)
    {
        $ride_request = RideRequest::where('id', $order->ride_request)->first();

        $trip = Trips::where('request_id', $ride_request->id)
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'Cancelled')
            ->first();

        if ($trip) {
            $trip->status = 'Begin trip';
            $trip->begin_trip = \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s');

            try {
                $trip->pickup_latitude = $request->latitude;
                $trip->pickup_longitude = $request->longitude;
                $trip->pickup_location = $this->request_helper->GetLocation($trip->pickup_latitude, $trip->pickup_longitude);
            } catch (\Exception $e) {
                $trip->pickup_latitude = $ride_request->pickup_latitude;
                $trip->pickup_longitude = $ride_request->pickup_longitude;
                $trip->pickup_location = $ride_request->pickup_location;
            }

            $trip->save();
        }

        return array([
            'status_code' => '1',
            'status_message' => 'Trip started.',
        ]);
    }
    /**
     * Finish delivery trip
     *
     * @return success or fail
     */
    public function end_delivery_trip($request, $order, $driver)
    {
        $ride_request = RideRequest::where('id', $order->ride_request)->first();

        $trip = Trips::where('request_id', $ride_request->id)
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'Cancelled')
            ->first();

        if ($trip) {
            $trip->status = 'Completed';
            $trip->end_trip = \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s');

            try {
                $trip->drop_latitude = $request->latitude;
                $trip->drop_longitude = $request->longitude;
                $trip->drop_location = $this->request_helper->GetLocation($trip->drop_latitude, $trip->drop_longitude);
                $trip->total_km = $order->distance / 1000;
                $trip->total_time = (float)(Carbon::create($trip->begin_trip)->diffInMinutes(Carbon::create($trip->end_trip)));
            } catch (\Exception $e) {
                $trip->drop_latitude = $ride_request->drop_latitude;
                $trip->drop_longitude = $ride_request->drop_longitude;
                $trip->drop_location = $ride_request->drop_location;
                $trip->total_km = $order->distance / 1000;
                $trip->total_time = (float)(Carbon::create($trip->begin_trip)->diffInMinutes(Carbon::create($trip->end_trip)));
            }

            $trip->save();

            $data = [
                'trip_id' => $trip->id,
                'correlation_id' => null,
                'driver_payout_status' => ($trip->driver_payout) ? 'Pending' : 'Completed',
            ];

            Payment::updateOrCreate(['trip_id' => $trip->id], $data);
        }

        return array([
            'status_code' => '1',
            'status_message' => 'Trip ended.',
        ]);
    }
}
