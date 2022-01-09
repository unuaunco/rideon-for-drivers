<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HomeDeliveryOrder;
use App\Models\DriverLocation;
use App\Models\Request as RideRequest;
use App\Models\Trips;

use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    public function show($trackingId)
    {
        $time = $direction = '';
        $getTrackingDetails = DB::table('delivery_orders as do')
            ->where('do.tracking_link', $trackingId)
            ->leftJoin('users as u', 'u.id', 'do.customer_id')
            ->leftJoin('merchants as m', 'm.id', 'do.merchant_id')
            ->leftJoin('request as r', 'r.id', 'do.ride_request')
            ->whereIn('do.status', ['assigned', 'picked_up', 'delivered'])
            ->select([
                'do.id as orderId', 
                'do.status as orderStatus', 
                'do.eta as eta', 
                'do.ride_request as rideRequestId', 
                'do.driver_id as driverId', 
                DB::raw('CONCAT_WS(" ", u.first_name, u.last_name) as clientName'), 
                'm.name as merchantName', 
                'r.pickup_latitude as pickupLatitude', 
                'r.pickup_longitude as pickupLongitude', 
                'r.drop_latitude as dropLatitude', 
                'r.drop_longitude as dropLongitude', 
                'r.pickup_location as pickupLocation', 
                'r.drop_location as dropLocation'
            ])
            ->first();

        if (!empty($getTrackingDetails)) {
            $getTrackingDetails->driverName = null;
            $getTrackingDetails->driverPhoto = null;

            $getDriverDetails = DB::table('users as u')
                ->selectRaw('CONCAT_WS(" ", u.first_name) as driverName, p.src as driverPhoto')
                ->join('profile_picture as p', 'p.user_id', 'u.id')
                ->where('u.id', $getTrackingDetails->driverId)
                ->first();

            if (!empty($getDriverDetails)) {
                $getTrackingDetails->driverName = $getDriverDetails->driverName;
                $getTrackingDetails->driverPhoto = $getDriverDetails->driverPhoto;
            }
            if($getTrackingDetails->orderStatus != 'delivered'){
                $driver_loc = DriverLocation::selectRaw('latitude,longitude')
                    ->where('user_id', $getTrackingDetails->driverId)
                    ->first();

                $getTrackingDetails->driverLatitude = $driver_loc->latitude;
                $getTrackingDetails->driverLongitude = $driver_loc->longitude;
            }

            switch ($getTrackingDetails->orderStatus) {
                case 'assigned':
                    try {
                        $time = $getTrackingDetails->eta . ' minutes';
                    } catch (\Exception $e) {
                        $time = 'unknown';
                    }
                    // $direction = 'to pick up';
                    $direction = 'En route to pick-up';
                    break;
                case 'picked_up':
                    try {
                        $time = $getTrackingDetails->eta . ' minutes';
                    } catch (\Exception $e) {
                        $time = 'unknown';
                    }
                    // $direction = 'to drop off';
                    $direction = 'En route to drop-off';
                    break;
                case 'delivered':
                    $direction = 'Order already delivered';
                    $time = '0 Minutes';
                    break;
            }
            $getTrackingDetails->estimateDeliverTime = $time;
            $getTrackingDetails->directionStatus = $direction;

            
        }
        return response()->json($getTrackingDetails);
    }
}
