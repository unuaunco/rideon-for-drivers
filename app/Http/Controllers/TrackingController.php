<?php

/**
 * Tracking Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Tracking
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HomeDeliveryOrder;
use App\Models\DriverLocation;
use App\Models\Request as RideRequest;
use App\Models\Trips;
use DB;

class TrackingController extends Controller
{
    public function __construct()
    {
        $this->request_helper = resolve('App\Http\Helper\RequestHelper');
    }
    public function index(){
        return view('tracking.index');
    }
    public function showTracking($tracking_id)
    {
        $time = $direction= '';
        $getTrackingDetails = DB::table('delivery_orders as do')
        ->selectRaw('do.id as order_id, do.status, do.eta as eta, do.ride_request, do.driver_id, CONCAT_WS(" ", u.first_name, u.last_name) as full_name, m.name as merchant_name, r.pickup_latitude, r.pickup_longitude, r.drop_latitude, r.drop_longitude, r.pickup_location, r.drop_location')
        ->leftJoin('users as u','u.id','do.customer_id')
        ->leftJoin('merchants as m','m.id','do.merchant_id')
        ->leftJoin('request as r','r.id','do.ride_request')
        ->where('do.tracking_link', $tracking_id)
        ->whereIn('do.status', ['assigned', 'picked_up', 'delivered'])
        ->first();
        if(!empty($getTrackingDetails)) {
            $getTrackingDetails->driver_name = null;
            $getTrackingDetails->driver_photo = null;
            $getDriverDetails = DB::table('users as u')
            ->selectRaw('CONCAT_WS(" ", u.first_name) as driver_name, p.src as driver_photo')
            ->join('profile_picture as p', 'p.user_id', 'u.id')
            ->where('u.id',$getTrackingDetails->driver_id)
            ->first();
            if(!empty($getDriverDetails)) {
                $getTrackingDetails->driver_name = $getDriverDetails->driver_name;
                $getTrackingDetails->driver_photo = $getDriverDetails->driver_photo;
            }
            $driver_loc = DriverLocation::selectRaw('latitude,longitude')->where('user_id',$getTrackingDetails->driver_id)->first();
            $getTrackingDetails->driver_latitude = $driver_loc->latitude;
            $getTrackingDetails->driver_longitude = $driver_loc->longitude;
            switch ($getTrackingDetails->status) {
                case 'assigned':
                    try{
                        // $get_fare_estimation = $this->request_helper->GetDrivingDistance($getTrackingDetails->pickup_latitude, $driver_loc->latitude,$getTrackingDetails->pickup_longitude, $driver_loc->longitude);

                        // if ($get_fare_estimation['status'] == "success") {
                        //     $get_fare_estimation['time'] = ($get_fare_estimation['time'] == '') ? 'unknown' : $get_fare_estimation['time'];
                        // } else {
                        //     $get_fare_estimation['time'] = 'unknown';
                        // }
                        // $time = (int)($get_fare_estimation['time']/60) . ' minutes';
                        $time = $getTrackingDetails->eta . ' minutes';
                    }
                    catch(\Exception $e) { $time = 'unknown'; }
                    // $direction = 'to pick up';
                    $direction = 'En route to pick-up';
                    break;
                case 'picked_up':
                    try{
                        $time = $getTrackingDetails->eta . ' minutes';
                        // $get_fare_estimation = $this->request_helper->GetDrivingDistance($getTrackingDetails->drop_latitude, $driver_loc->latitude,$getTrackingDetails->drop_longitude, $driver_loc->longitude);
                        // if ($get_fare_estimation['status'] == "success") {
                        //     $get_fare_estimation['time'] = ($get_fare_estimation['time'] == '') ? 'unknown' : $get_fare_estimation['time'];
                        // } else {
                        //     $get_fare_estimation['time'] = 'unknown';
                        // }
                        // $time = (int)($get_fare_estimation['time']/60) . ' minutes';
                    }
                    catch(\Exception $e) { $time = 'unknown';}
                    // $direction = 'to drop off';
                    $direction = 'En route to drop-off';
                    break;
                case 'delivered':
                    $direction = 'Order already delivered';
                    $time = '0 Minutes';
                    break;
            }
            $getTrackingDetails->estimate_deliver_time = $time;
            $getTrackingDetails->direction_status = $direction;
            return view('tracking.tracking_order_details')->with('tracking_details',$getTrackingDetails)->with('title', 'Tracking Order #'.$getTrackingDetails->order_id);
        }
        abort(404);
    }

    public function storeTrackingId($order_id=null) {
        try {
            if(!empty($order_id)) {
                $trackingId = $this->getTrackingId();
                HomeDeliveryOrder::where('id',$order_id)->update(['tracking_link' => $trackingId]);
                return $trackingId;
            }
        } catch(\Exception $e){errorLog($e);}
    }

    public function getTrackingId() {
        $currentTimeStamp = time();
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 14; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $trackingId = $randomString . $currentTimeStamp;
        return $trackingId;
    }
}