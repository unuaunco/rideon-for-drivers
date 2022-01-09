<?php

/**
 * Integration Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Integration
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers\Api;

use App;
use App\Http\Controllers\Controller;
use App\Models\BadOrders;
use App\Models\DriverLocation;
use App\Models\HomeDeliveryOrder;
use App\Models\Language;
use App\Models\Merchant;
use App\Models\Request as RideRequest;
use App\Models\User;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;

class IntegrationController extends Controller
{
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->request_helper = resolve('App\Http\Helper\RequestHelper');
        $this->map_service = resolve('App\Services\MapServices\HereMapService');
    }

    /**
     * Webhook for integration with Yelo
     * @param Get method request inputs
     *
     * @return Response Json
     */
    public function yelo(Request $request)
    {
        $server_key = $_GET['secret_key'];
        if (!$server_key) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        $merchant = Merchant::selectRaw('id,name,delivery_fee,delivery_fee_base_distance,delivery_fee_per_km')->where('shared_secret', $server_key)->first();
        if (!$merchant) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        if ($request->isMethod("POST")) {
            $data = [];
            $order_status = $request->input('job_status');
            $order_delivery = $request->input('has_delivery');
            if (($order_status == 10 || $order_status == 12) && $order_delivery == 1) {
                try {
                    $merchant_id = $merchant->id;
                    $new_orders = array();

                    //pick up Location data
                    $data['pick_up_location'] = $request->input('job_pickup_address');
                    try {
                        $pickup_geocode = $this->request_helper->GetLatLng($data['pick_up_location']);
                        $data['pick_up_latitude'] = $pickup_geocode[0];
                        $data['pick_up_longitude'] = $pickup_geocode[1];
                    } catch (\Exception $e) {
                        logger('getting pick up location error : ' . $e->getMessage());
                    }

                    //drop off Location data
                    $data['drop_off_location'] = $request->input('job_address');
                    $data['drop_off_latitude'] = $request->input('job_latitude');
                    $data['drop_off_longitude'] = $request->input('job_longitude');
                    try {
                        if (empty($data['drop_off_latitude'])) {
                            $dropoff_geocode = $this->request_helper->GetLatLng($data['drop_off_location']);
                            $data['drop_off_longitude'] = $dropoff_geocode[1];
                            $data['drop_off_latitude'] = $dropoff_geocode[0];
                        }
                    } catch (\Exception $e) {
                        logger('getting drop off location error : ' . $e->getMessage());
                    }

                    //Customer data
                    //Cut australian code from client mobile number
                    $full_mobile_number = $request->input('customer_phone');

                    $data['mobile_number'] = substr($full_mobile_number, -10);
                    $data['country_code'] = str_replace("+", "", str_replace($data['mobile_number'], "", $full_mobile_number));

                    $full_name = $request->input('customer_username');
                    $name_arr = explode(" ", $full_name, 2);

                    $data['first_name'] = $name_arr[0];
                    $data['last_name'] = !empty($name_arr[1]) ? $name_arr[1] : 'Unknown';
                    $data['email'] = $request->input('customer_email', $data['mobile_number'] . "@none.exist");

                    $data['delivery_fee'] = !empty($request->input('delivery_charge')) ? $request->input('delivery_charge') : null;
                    $data['delivery_fee_currency'] = $request->input('currency_code');

                    $user = $this->get_or_create_rider((object) $data);
                    $ride_request = $this->create_ride_request((object) $data, $user);

                    //create order
                    $new_order = new HomeDeliveryOrder;
                    $arrived_datetime = $request->input('job_delivery_datetime');
                    $accepted_time = \Carbon\Carbon::now();
                    $fulfill_time = new \Carbon\Carbon($arrived_datetime);
                    $est_time = $fulfill_time->diffInMinutes($accepted_time);
                    if ($est_time > 60) {
                        $new_order->status = 'pre_order';
                    }
                    $est_time = $est_time - 15;
                    //calculate distance
                    if(HERE_REST_KEY != ""){
                                $req = (object)array(
                                    "origin_latitude" => $data['pick_up_latitude'] , 
                                    "origin_longitude" => $data['pick_up_longitude'], 
                                    "destination_latitude" => $data['drop_off_latitude'], 
                                    "destination_longitude" => $data['drop_off_longitude']
                                );
                                $get_fare_estimation['distance'] = $this->map_service->getDistance($req);
                    }
                    else{
                        $get_fare_estimation = $this->request_helper->GetDrivingDistance($data['pick_up_latitude'], $data['drop_off_latitude'], $data['pick_up_longitude'], $data['drop_off_longitude']);
                        if ($get_fare_estimation['status'] == "success") {
                            if ($get_fare_estimation['distance'] == '') {
                                $get_fare_estimation['distance'] = 0;
                            }
                        }
                        else{
                            $get_fare_estimation['distance'] = 0;
                        }
                    }

                    $new_order->distance = $get_fare_estimation['distance'];
                    $new_order->estimate_time = $est_time;
                    $new_order->fee = 0;
                    $new_order->customer_id = $user->id;
                    $new_order->ride_request = $ride_request->id;

                    $new_order->merchant_id = $merchant_id;
                    $new_order->order_description = $request->input('job_description');
                    $fee = 0.0;
                    if (($get_fare_estimation['distance'] / 1000) > $merchant->delivery_fee_base_distance) {
                        $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance'] / 1000 - $merchant->delivery_fee_base_distance);
                    } else {
                        $fee = $merchant->delivery_fee;
                    }
                    $new_order->fee = round($fee, 2);

                    $new_order->save();
                    array_push($new_orders, [
                        'order_id' => $new_order->id,
                    ]);

                    logger($new_order->id . ' fulfill time : ' . $fulfill_time);
                    $this->notify_drivers((object) $data, 'New job(s) in your location');

                    return response()->json([
                        'status' => 'Successfully created',
                        'orders' => $new_orders,
                    ]);
                } catch (\Exception $e) {
                    errorLog($e);
                    $data = $request->all();
                    //In case of bad order data put that order information into db to manual inserting
                    $bad_order = new BadOrders;
                    $bad_order->secret = $server_key;
                    $bad_order->description = json_encode($data);
                    $bad_order->save();
                    $emails = ['pardusurbanus@protonmail.com'];
                    $content = [
                        'first_name' => '_',
                    ];
                    $data['content'] = json_encode($data, JSON_PRETTY_PRINT);
                    $data['first_name'] = $content['first_name'];
                    $data['merchant'] = $merchant->name;
                    // Send Forgot password email to give user email
                    foreach ($emails as $email) {
                        Mail::send('emails.bad_order', $data, function ($message) use ($email, $content) {
                            $message->to($email, $content['first_name'])->subject('Ride On New bad data order');
                            $message->from('api@rideon.group', 'Ride on Tech support');
                        });
                    }
                    return response()->json(['status' => 'Bad request data', 'error' => $e->getMessage()], 400);
                }
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
            $user->password = Str::random();
            $user->country_code = $request->country_code;
            $user->language = $language;
            $user->email = $request->email;
            $user->currency_code = get_currency_from_ip();

            $user->save();
        }
        return $user;
    }

    /**
     * Notify nearest drivers
     *
     * @return success or fail
     */
    public function notify_drivers($request, $message)
    {
        $nearest_cars = DriverLocation::select(DB::raw('*, ( 6371 * acos( cos( radians(' . $request->pick_up_latitude . ') ) * cos( radians( latitude ) ) * cos(radians( longitude ) - radians(' . $request->pick_up_longitude . ') ) + sin( radians(' . $request->pick_up_latitude . ') ) * sin( radians( latitude ) ) ) ) as distance'))
            ->having('distance', '<=', 15)->get();

        foreach ($nearest_cars as $nearest_car) {
            $driver_details = User::where('id', $nearest_car->user_id)->first();

            if ($driver_details->device_id != "" && $driver_details->status == "Active") {
                $this->send_custom_pushnotification($driver_details->device_id, $driver_details->device_type, $driver_details->user_type, $message);
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
        $polyline = null;
        try {
            $polyline = $this->request_helper->GetPolyline($request->pick_up_latitude, $request->drop_off_latitude, $request->pick_up_longitude, $request->drop_off_longitude);
        } catch (\Exception $e) {
            logger('polyline getting exception : ' . $e->getMessage());
        }

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
        $ride_request->trip_path = $polyline ? $polyline : '';
        $ride_request->payment_mode = 'Stripe';
        $ride_request->status = 'Accepted';
        //TODO: change to default timezone
        $ride_request->timezone = 'Australia/Melbourne';
        $ride_request->location_id = '1';
        $ride_request->additional_fare = '';
        $ride_request->peak_fare = '0';
        $ride_request->save();

        return $ride_request;
    }
}
