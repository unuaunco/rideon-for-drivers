<?php

/**
 * Cron Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Cron
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ScheduleRide;
use App\Models\PeakFareDetail;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Merchant;
use App\Models\ReferralUser;
use App\Models\DriverLocation;
use App\Models\Currency;
use App\Models\Trips;
use App\Models\Payment;
use App\Models\HomeDeliveryOrder;
use Carbon\Carbon;
use App\Models\Request as RideRequest;
use App\Models\CronJob;

use App\Http\Controllers\PayoutController;

use DB;
use App;

class CronController extends Controller
{
	public function __construct()
	{
		$this->request_helper = resolve('App\Http\Helper\RequestHelper');
        $this->map_service = resolve('App\Services\MapServices\HereMapService');
        $stripe_key = payment_gateway('secret','Stripe');
        $api_version = payment_gateway('api_version','Stripe');
        \Stripe\Stripe::setApiKey($stripe_key);
        \Stripe\Stripe::setApiVersion($api_version);
    }

    /**
	 * Cron request to update pre-orders status
	 * @param
	 * @return Response Json
	 */    
    public function updatePreOrders(){

        $pre_orders = HomeDeliveryOrder::where('status','pre_order')->get();

        foreach ($pre_orders as $pre_order){
            $due_time = $pre_order->created_at->addMinutes($pre_order->estimate_time);
            $est_time = $due_time->diffInMinutes(Carbon::now());

            if ($est_time < 60){
                $pre_order->status = 'new';
                $pre_order->save();

                $request = RideRequest::where('id', $pre_order->ride_request)->first();

                $this->notify_drivers($request, 'New job(s) in your location');

                return response()->json(['status' => true, 'status_message' => 'updated successfully']);
            }
        }

        return response()->json(['status' => true, 'status_message' => 'updated successfully']);
    }

    /**
    * Cron request to update ETA from assigned and picked_up orders
    * @param
    * @return Response Json
    */
    public function updateEta(){
        $orders = HomeDeliveryOrder::whereIn('status', ['assigned', 'picked_up'])->get();
        foreach($orders as $order){
            //$create_time = Carbon::createFromTimeString($order->created_at);
            $create_time = Carbon::now();
            $fulfill_time = Carbon::createFromTimeString($order->created_at)->addMinutes($order->estimate_time);
            $duration = $create_time->diffInMinutes($fulfill_time);
            if($duration < 60){
                try{
                    $request_loc = RideRequest::find($order->ride_request);
                    $driver_loc = DriverLocation::where('user_id', $order->driver_id)->first();

                    if($order->status == 'assigned'){
                        $destination_latitude = $request_loc->pickup_latitude;
                        $destination_longitude = $request_loc->pickup_longitude;
                    }
                    else{
                        $destination_latitude = $request_loc->drop_latitude;
                        $destination_longitude = $request_loc->drop_longitude;
                    }

                    if(HERE_REST_KEY != ""){
                        $req = (object)array("origin_latitude" => $driver_loc->latitude , "origin_longitude" => $driver_loc->longitude, "destination_latitude" => $destination_latitude , "destination_longitude" => $destination_longitude);
                        $get_fare_estimation['time'] = $this->map_service->getETA($req);
                    }
                    else{
                        $get_fare_estimation = $this->request_helper->GetDrivingDistance($destination_latitude, $driver_loc->latitude, $destination_longitude, $driver_loc->longitude);
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
                        
                    $eta = (int)$get_fare_estimation['time'];
                    if($eta == 0){
                        $eta = 1;
                    }
                    $order->eta = $eta;
                    $order->save();
                }
                catch(\Exception $e){
                    logger('Error on eta update on order #' . $order->id . 'The error is: ' . $e->getMessage());
                }
            }
        }
    }

    /**
	 * Cron request to send payments
	 * @param
	 * @return Response Json
	 */ 
    public function dailyPayoutsToDrivers(){

        $calculation_date = Carbon::now()->subDays(3)->toDateString();

        if(App::environment(['production'])) {
            $transfer_base_link = 'https://dashboard.stripe.com/connect/transfers/';
        }
        else{
            $transfer_base_link = 'https://dashboard.stripe.com/test/connect/transfers/';
        }

        $orders = HomeDeliveryOrder::whereDate('delivered_at', $calculation_date)->where('status','delivered')->get();

        $drivers = $orders->pluck('driver_id')->unique();

        foreach($drivers as $drv_id){
            $driver = User::find($drv_id);
            $trip_details       = Trips::DriverPayoutTripsOnly()->where('driver_id', $driver->id)->whereDate('end_trip', '=', $calculation_date)->get();
            
            $trip_amount        = $trip_details->sum('driver_payout');
            $trip_ids           = $trip_details->pluck('id')->toArray();

            if(count($trip_ids) == 0 || $trip_amount <= 0) {
                logger('Invalid Request.Please Try Again.');
            }
            else{
                $trip_currency      = $trip_details[0]->currency_code;
                $transaction_details = array(
                    'user_id'           => $driver->id,
                    'type'              => 'Payout',
                    'amount'            => $trip_amount / 1.1,
                    'amount_with_tax'   => $trip_amount,
                    'currency'          => 4,
                    'status_description'=> 'Transfer failed',
                    'calculation_date'  => $calculation_date,
                );

                $payout_details     = $trip_details[0]->driver->default_payout_credentials;

                if($payout_details == null) {
                    $transaction_details['description'] = 'Transfer to ' . $driver->first_name . ' ' . $driver->last_name . ' failed. Need to update bank details on mobile app.';
                }
                else{
                    $payout_data = array();

                    if($payout_details->type == 'Stripe') {
                        $payout_data['currency'] = $payout_details->payout_preference->currency_code ? $payout_details->payout_preference->currency_code : "AUD";
                        $payout_data['amount'] = currencyConvert($trip_currency, $payout_data['currency'], $trip_amount);
                        try{
                            $cna = \Stripe\Account::retrieve($payout_details->payout_id,[]);

                            if($cna->individual->verification->status == 'unverified'){
                                logger('Yet, driver doesn\'t enter his Payout details. Cannot Make Payout.');
                                $transaction_details['description'] = 'Driver\'s account on Stripe unverified. Driver need to upload properly Photo ID document or Driver\'s license.';
                            }
                            else if(!$cna->payouts_enabled){
                                logger('Yet, driver doesn\'t enter his Payout details. Cannot Make Payout.');
                                $transaction_details['description'] = 'Driver has payouts option disabled. Need to check driver\'s connected account on stripe. Account ID is ' . $payout_details->payout_id;
                            }
                            else{
                                $payout_service = resolve('App\Services\Payouts\\'.$payout_details->type.'Payout');
                                $pay_result = $payout_service->makePayout($payout_details->payout_id,$payout_data);

                                if(!$pay_result['status']){
                                    $transaction_details['description'] = 'Payout Failed : '.$pay_result['status_message'];
                                    logger($transaction_details['description']);
                                }
                                else{
                                    $payment_data['driver_transaction_id']   = $pay_result['transaction_id'];
                                    $payment_data['driver_payout_status']   = isset($pay_result['is_pending']) ? 'Processing':'Paid';

                                    Payment::whereIn('trip_id',$trip_ids)->update($payment_data);

                                    logger($pay_result['status_message']);

                                    $transaction_details['status_description'] = 'Transfer sent';
                                    $transaction_details['description'] = 'Transfer to ' . $driver->first_name . ' ' . $driver->last_name . ' successfully sent.';
                                    $transaction_details['object_link'] = $transfer_base_link . $payment_data['driver_transaction_id'] . '/receipt';
                                    $transaction_details['object_id'] = $payment_data['driver_transaction_id'];
                                }
                            }
                        }
                        catch(\Exception $e){
                            logger($e->getMessage());
                            $transaction_details['description'] = 'Transfer to ' . $driver->first_name . ' ' . $driver->last_name . ' failed. Please, contact developers on this error.';
                        }
                    }
                }
                Transaction::updateOrCreate($transaction_details);
            }
        }
        return response()->json(['status' => true, 'status_message' => 'Payments sent']);

    }

    /**
	 * Cron request to send invoices
	 * @param
	 * @return Response Json
	 */ 
    public function dailyInvoicesToMerchants(){

        $calculation_date = Carbon::now()->toDateString();

        if(App::environment(['production'])) {
            $invoice_base_link = 'https://dashboard.stripe.com/invoices/';
        }
        else{
            $invoice_base_link = 'https://dashboard.stripe.com/test/invoices/';
        }

        $orders = HomeDeliveryOrder::whereDate('delivered_at', $calculation_date)->where('status','delivered')->get();
        $merchants = $orders->pluck('merchant_id')->unique();

        foreach ($merchants as $merchant){
            $merch = Merchant::find($merchant);

            $transaction_details = array(
                    'user_id'           => $merch->user_id,
                    'type'              => 'Invoice',
                    'currency'          => 4,
                    'status_description'=> 'Invoice failed',
                    'calculation_date'  => $calculation_date,
            );
            try{
                if($merch->stripe_id && $merch->invoicing_schedule == 'Daily'){

                    $merch_orders = $orders->where('merchant_id', $merch->id);
                    $amount_sum = 0.00;

                    foreach ($merch_orders as $merch_order){
                        $ride_req = RideRequest::find($merch_order->ride_request);
                        $ride_address = $ride_req->drop_location;
                        $descr = 'Delivery to ' . $ride_address;
                        $amount = $merch_order->fee;
                        if($ride_req->peak_fare != 0 && $merch->id != '84'){
                            //Create Item for Manual Order Surcharge
                            \Stripe\InvoiceItem::create([
                                'customer' => $merch->stripe_id,
                                'amount' => (int)round(site_settings('manual_surchange')*100, 2),
                                'currency' => 'aud',
                                'description' => '(Manual Order Surcharge) ' . $descr,
                                'tax_rates' => ['txr_1GU8lNBWmHDX39LGuxXTjGED'],
                            ]);
                        }
                        //Create Order Item for Invoice
                        \Stripe\InvoiceItem::create([
                            'customer' => $merch->stripe_id,
                            'amount' => (int)round($amount*100, 2),
                            'currency' => 'aud',
                            'description' => $descr,
                            'tax_rates' => ['txr_1GU8lNBWmHDX39LGuxXTjGED'],
                        ]);
                        $amount_sum += $amount;
                    }
                    $invoice = \Stripe\Invoice::create([
                        'customer' => $merch->stripe_id,
                        'auto_advance' => true,
                        'collection_method' => 'charge_automatically',
                        'statement_descriptor' => 'Ride On ' . $calculation_date,
                    ]);
                    $transaction_details['status_description']  = 'Invoice sent';
                    $transaction_details['description']         = 'Invoice to ' . $merch->name . ' successfully sent.';
                    $transaction_details['object_link']         = $invoice_base_link . $invoice->id;
                    $transaction_details['object_id']           = $invoice->id;
                    $transaction_details['amount']              = $amount_sum;
                    $transaction_details['amount_with_tax']     = $amount_sum * 1.1;
                    Transaction::updateOrCreate($transaction_details);
                }
                else{
                    if($merch->invoicing_schedule == 'Daily'){
                        $transaction_details['amount']      = 0.00;
                        $transaction_details['description'] = 'Invoice to ' . $merch->name .' failed. Check stripe_id of merchant connected account.';
                        Transaction::updateOrCreate($transaction_details);
                    }
                }
            }
            catch(\Exception $e){
                logger($e->getMessage());
                $transaction_details['amount']      = 0.00;
                $transaction_details['description'] = 'Invoice to ' . $merch->name .' failed. Please, contact developers on this error.';
                Transaction::updateOrCreate($transaction_details);
            }   
        }

        return response()->json(['status' => true, 'status_message' => 'Invoices sent']);
    }

        /**
	 * Cron request to send invoices weekly
	 * @param
	 * @return Response Json
	 */ 
    public function weeklyInvoicesToMerchants(){

        $calculation_date = Carbon::now()->toDateString();
        $calculation_date_begin = Carbon::now()->subDays(6)->toDateString();

        if(App::environment(['production'])) {
            $invoice_base_link = 'https://dashboard.stripe.com/invoices/';
        }
        else{
            $invoice_base_link = 'https://dashboard.stripe.com/test/invoices/';
        }

        $merchants = Merchant::where('invoicing_schedule','Weekly')->get();

        foreach ($merchants as $merchant){
            if($merchant->stripe_id && $merchant->invoicing_schedule == 'Weekly'){
                $orders = HomeDeliveryOrder::where('merchant_id', $merchant->id)->whereDate('delivered_at', '<=', $calculation_date)->whereDate('delivered_at', '>=', $calculation_date_begin)->where('status','delivered')->get();

                $transaction_details = array(
                        'user_id'           => $merchant->user_id,
                        'type'              => 'Invoice',
                        'currency'          => 4,
                        'status_description'=> 'Invoice failed',
                        'calculation_date'  => $calculation_date,
                );

                try{
                    if($orders){
                        $amount_sum = 0.00;

                        foreach ($orders as $merch_order){
                            $ride_req = RideRequest::find($merch_order->ride_request);
                            $ride_address = $ride_req->drop_location;
                            $descr = 'Delivery to ' . $ride_address;
                            $amount = $merch_order->fee;

                            if($ride_req->peak_fare != 0){
                                $amount += site_settings('manual_surchange');
                                $descr = '(Manual order) ' . $descr;
                                //Create Item for Manual Order Surcharge
                                \Stripe\InvoiceItem::create([
                                    'customer' => $merchant->stripe_id,
                                    'amount' => (int)round(site_settings('manual_surchange')*100, 2),
                                    'currency' => 'aud',
                                    'description' => '(Manual Order Surcharge) ' . $descr,
                                    'tax_rates' => ['txr_1GU8lNBWmHDX39LGuxXTjGED'],
                                ]);
                            }
                            //Create Order Item for Invoice
                            \Stripe\InvoiceItem::create([
                                'customer' => $merchant->stripe_id,
                                'amount' => (int)round($amount*100, 2),
                                'currency' => 'aud',
                                'description' => $descr,
                            ]);
                            $amount_sum += $amount;
                        }
                        $invoice = \Stripe\Invoice::create([
                            'customer' => $merchant->stripe_id,
                            'auto_advance' => false,
                            'default_tax_rates' => ['txr_1GU8lNBWmHDX39LGuxXTjGED'],
                            'collection_method' => 'send_invoice',
                            'days_until_due' => '1',
                        ]);
                        $transaction_details['status_description']  = 'Invoice sent';
                        $transaction_details['description']         = 'Invoice to ' . $merchant->name . ' successfully sent.';
                        $transaction_details['object_link']         = $invoice_base_link . $invoice->id;
                        $transaction_details['object_id']           = $invoice->id;
                        $transaction_details['amount']              = $amount_sum;
                        $transaction_details['amount_with_tax']     = $amount_sum * 1.1;
                        Transaction::updateOrCreate($transaction_details);

                    }
                }
                catch(\Exception $e){
                    logger($e->getMessage());
                    $transaction_details['amount']      = 0.00;
                    $transaction_details['description'] = 'Invoice to ' . $merchant->name .' failed. Please, contact developers on this error.';
                    Transaction::updateOrCreate($transaction_details);
                }
            }
            if($merchant->invoicing_schedule == 'Weekly'){
                        $transaction_details['amount']      = 0.00;
                        $transaction_details['description'] = 'Invoice to ' . $merchant->name .' failed. Check stripe_id of merchant connected account.';
                        Transaction::updateOrCreate($transaction_details);
            }

        }

        return response()->json(['status' => true, 'status_message' => 'Invoices sent']);
    }

    /**
	 * Cron request to cars for scheduled ride
	 * @param
	 * @return Response Json
	 */
	public function requestCars()
	{
		// before 5 min from schedule time
		$ride = ScheduleRide::where('status','Pending')->get();

		if($ride->count() == 0) {
			return '';			
		}

		foreach ($ride as $request_val) {   
			if($request_val->timezone) {
				date_default_timezone_set($request_val->timezone);
			}
		
			$current_date = date('Y-m-d');				
			$current_time = date('H:i');
            if(strtotime($request_val->schedule_date) == strtotime($current_date) && strtotime($request_val->schedule_time) == (strtotime($current_time) + 300)){
				$additional_fare = "";
				$peak_price = 0;

				if(isset($request_val->peak_id)!='') {
				   $fare = PeakFareDetail::find($request_val->peak_id);
					if($fare) {
						$peak_price = $fare->price; 
						$additional_fare = "Peak";
					}
				}

	            $schedule_id = $request_val->id;
				$payment_mode = $request_val->payment_method;
				$is_wallet = $request_val->is_wallet;

				$data = [ 
					'rider_id' =>$request_val->user_id,
					'pickup_latitude' => $request_val->pickup_latitude,
					'pickup_longitude' => $request_val->pickup_longitude,
					'drop_latitude' => $request_val->drop_latitude,
					'drop_longitude' => $request_val->drop_longitude,
					'user_type' => 'rider',
					'car_id' => $request_val->car_id,
					'driver_group_id' => null,
					'pickup_location' => $request_val->pickup_location,
					'drop_location' => $request_val->drop_location,
					'payment_method' => $payment_mode,
					'is_wallet' => $is_wallet,
					'timezone' => $request_val->timezone,
					'schedule_id' => $schedule_id,
					'additional_fare'  =>$additional_fare,
					'location_id' => $request_val->location_id,
					'peak_price'  => $peak_price,
					'booking_type'  => $request_val->booking_type, 
					'driver_id'  => $request_val->driver_id, 
				];
					
				if ($request_val->driver_id==0) {
					$car_details = $this->request_helper->find_driver($data);
				}
				else {
					$car_details = $this->request_helper->trip_assign($data);
				}
            }
            elseif(strtotime($request_val->schedule_date.' '.$request_val->schedule_time) == strtotime(date('Y-m-d H:i')) + 1800) {
		        $rider = User::find($request_val->user_id);
            	if ($request_val->booking_type=='Manual Booking' && $request_val->driver_id!=0) {
	            	$driver_details = User::find($request_val->driver_id);
		            $push_data['push_title'] = __('messages.api.schedule_remainder');
		            $push_data['data'] = array(
		                'manual_booking_trip_reminder' => array(
		                	'date' 	=> $request_val->schedule_date,
		                	'time'	=> $request_val->schedule_time,
		                	'pickup_location' 		=> $request_val->pickup_location,
		                	'pickup_latitude' 		=> $request_val->pickup_latitude,
		                	'pickup_longitude' 		=> $request_val->pickup_longitude,
		                	'rider_first_name'		=> $rider->first_name,
		                	'rider_last_name'		=> $rider->last_name,
		                	'rider_mobile_number'	=> $rider->mobile_number,
		                	'rider_country_code'	=> $rider->country_code
		                )
		            );

		            $this->request_helper->SendPushNotification($rider,$push_data);

			        $text = trans('messages.trip_booked_driver_remainder',['date'=>$request_val->schedule_date.' ' .$request_val->schedule_time,'pickup_location'=>$request_val->pickup_location,'drop_location'=>$request_val->drop_location]);
			        
			        $to = $driver_details->phone_number;
			        $this->request_helper->send_message($to,$text);
			    }

			    //booking message to user
	            $text = trans('messages.trip_booked_user_remainder',['date'=>$request_val->schedule_date.' ' .$request_val->schedule_time]);
	            if ($request_val->booking_type=='Manual Booking' && $request_val->driver_id!=0) {
	            	$driver = User::find($request_val->driver_id);
	                $text = $text.trans('messages.trip_booked_driver_detail',['first_name'=>$driver->first_name,'phone_number'=>$driver->mobile_number]);
	                $text = $text.trans('messages.trip_booked_vehicle_detail',['name'=>$driver->driver_documents->vehicle_name,'number'=>$driver->driver_documents->vehicle_number]);
	            }
	            $to = $rider->phone_number;
	            $this->request_helper->send_message($to,$text);
            }
            else {
				if(strtotime($request_val->schedule_date) < strtotime($current_date)) {
                    $update_ride = ScheduleRide::find($request_val->id);
                    $update_ride->status ='Cancelled';
                    $update_ride->save();
				}
            }
        }
	}

	public function updateReferralStatus()
	{
		ReferralUser::where('end_date','<',date('Y-m-d'))->where('payment_status','Pending')->update(['payment_status' => 'Expired']);
		return response()->json(['status' => true, 'status_message' => 'updated successfully']);
	}

	public function updateOfflineUsers()
	{
		$offline_hours = site_settings('offline_hours');
		$minimumTimestamp = Carbon::now()->subHours($offline_hours);

		\DB::table('driver_location')->where('status','Online')->where('updated_at','<',$minimumTimestamp)->update(['status' => 'Offline']);
		return response()->json(['status' => true, 'status_message' => 'updated successfully']);
	}

	public function updateCurrency()
	{
		$return_data = array();
		$result = Currency::all();
		$result->each(function($row) use(&$return_data) {
			$rate = 1;
			try {
		        if($row->code != 'USD') {
		            $rate = \Swap::latest('USD/'.$row->code);
		            $rate = $rate->getValue();
		        }
		        Currency::where('code',$row->code)->update(['rate' => $rate]);
		        $return_data[] = ['status' => true,'status_message' => 'updated successfully','target' => $row->code,'value' => $rate];
			}
			catch(\Exception $e) {
				$return_data[] = ['status' => false,'status_message' => $e->getMessage(),'target' => $row->code];
			}
		});

		return response()->json($return_data);
	}

	public function updatePaypalPayouts()
	{
		$pending_payments = Payment::where('driver_payout_status','Processing')->orWhere('admin_payout_status','Processing')->get();
		if($pending_payments->count() == 0) {
			return response()->json(['status' => false, 'status_message' => 'No Pending Payouts found']);
		}

		$paypal_payout = resolve("App\Services\Payouts\PaypalPayout");
		$pending_payments->each(function($pending_payment) use($paypal_payout) {
			$batch_id = $pending_payment->correlation_id;
			$payment_data = $paypal_payout->fetchPayoutViaBatchId($batch_id);
			if($payment_data['status']) {
				$payout_data = $paypal_payout->getPayoutStatus($payment_data['data']);
				$trip = Trips::find($pending_payment->trip_id);

				if($payout_data['status']) {
					if($payout_data['payout_status'] == 'SUCCESS') {
						if($trip->driver->company_id == '1') {
							$pending_payment->driver_payout_status = "Paid";
							$pending_payment->driver_transaction_id = $payout_data['transaction_id'];
						}
						else {
							$pending_payment->admin_payout_status = "Paid";
							$pending_payment->admin_transaction_id = $payout_data['transaction_id'];
						}
					}

					if(in_array($payout_data['payout_status'], ['FAILED','RETURNED','BLOCKED'])) {
						if($trip->driver->company_id == '1') {
							$pending_payment->driver_payout_status = "Pending";
						}
						else {
							$pending_payment->admin_payout_status = "Pending";
						}
					}
					
					$pending_payment->save();
				}
			}
		});
		return response()->json(['status' => true, 'status_message' => 'updated successfully']);
    }
    

        /**
     * custom push notification
     *
     * @return success or fail
     */
    public function send_custom_pushnotification($device_id,$device_type,$user_type,$message)
    {   
        if (LOGIN_USER_TYPE=='company') {
            $push_title = "Message from ".Auth::guard('company')->user()->name;    
        }
        else {
            $push_title = "Message from ".SITE_NAME;   
        }

        try {
            if($device_type == 1) {
                $data       = array('custom_message' => array('title' => $message,'push_title'=>$push_title));
                $this->request_helper->push_notification_ios($message, $data, $user_type, $device_id,$admin_msg=1);
            }
            else {
                $data       = array('custom_message' => array('message_data' => $message,'title' => $push_title ));
                $this->request_helper->push_notification_android($push_title, $data, $user_type, $device_id,$admin_msg=1);
            }
        }
        catch (\Exception $e) {
            logger('Could not send push notification');
        }
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

                if($driver_details->device_id != "" && $driver_details->status == "Active")
                {    
                    $this->send_custom_pushnotification($driver_details->device_id,$driver_details->device_type,$driver_details->user_type,$message);    
                }
            }
    }
}