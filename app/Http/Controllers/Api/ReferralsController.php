<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helper\RequestHelper;
use App\Http\Start\Helpers;
use App\Models\User;
use App\Models\DriverAddress;
use App\Models\ReferralSetting;
use App\Models\ReferralUser;
use App\Models\RiderLocation;
use JWTAuth;
use App;
use DB;
use Validator;

class ReferralsController extends Controller
{
	// Global variable for Helpers instance
	protected $request_helper;

    public function __construct(RequestHelper $request)
    {
    	$this->request_helper = $request;
		$this->helper = new Helpers;
	}

	/**
	 * To Get the referral Users Details
	 * @param  Request $request Get values
	 * @return Response Json
	 */
	public function get_referral_details(Request $request)
	{
		$user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

		if ($user == '') {
			return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
		}

		$user_type = $user->user_type;

		$admin_referral_settings = ReferralSetting::whereUserType($user_type)->where('name','apply_referral')->first();

		$referral_amount = 0;
    	if($admin_referral_settings->value) {
        	$referral_amount = $admin_referral_settings->get_referral_amount($user_type);
		}

		$referral_users = ReferralUser::where('user_id',$user_details->id)->get();

		$pending_referrals = array();
		$completed_referrals = array();

		foreach ($referral_users as $referral_user) {
			$temp_details['id'] 			= $referral_user->id;
			$temp_details['name'] 			= $referral_user->referred_user_name;
            //handle bad profile image
            try{
			    $temp_details['profile_image'] 	= $referral_user->referred_user_profile_picture_src;
            }
            catch(\Exception $e){
                $temp_details['profile_image'] = '';
            }
			$temp_details['start_date'] 	= $referral_user->start_date;
			$temp_details['end_date'] 		= $referral_user->end_date;
			$temp_details['days'] 			= $referral_user->days;
			$temp_details['remaining_days'] = $referral_user->remaining_days;
			$temp_details['trips'] 			= $referral_user->trips;
			$temp_details['remaining_trips']= $referral_user->remaining_trips;
			$temp_details['earnable_amount']= $referral_user->earnable_amount;
			$temp_details['status'] 		= $referral_user->payment_status;

			if($referral_user->payment_status == 'Pending') {
				array_push($pending_referrals,$temp_details);
			}
			else {
				array_push($completed_referrals,$temp_details);
			}
		}

		return response()->json([
			'status_code' 			=> '1',
			'status_message' 		=> trans('messages.success'),
			'apply_referral' 		=> $admin_referral_settings->value,
			'referral_link' 		=> route('redirect_to_app',['type' => strtolower($user_type)]),
			'referral_code'  		=> $user->referral_code,
			'referral_amount' 		=> $referral_amount,
			'pending_amount' 		=> $user->pending_referral_amount,
			'total_earning'  		=> $user->total_referral_earnings,
			'pending_referrals' 	=> $pending_referrals,
			'completed_referrals' 	=> $completed_referrals,
		]);
    }

    /**
	 * To Get the referral Users Details
	 * @param  Request $request Get values
	 * @return Response Json
	 */
	public function get_passengers_for_drivers(Request $request)
	{
		$user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

		if ($user == '') {
			return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
		}

		$user_type = $user->user_type;

		$admin_referral_settings = ReferralSetting::whereUserType($user_type)->where('name','apply_referral')->first();

		$referral_amount = 0;
    	if($admin_referral_settings->value) {
        	$referral_amount = $admin_referral_settings->get_referral_amount($user_type);
		}

		$referral_users = ReferralUser::where('user_id',$user_details->id)->get();
		// $referral_users = ReferralUser::where('user_id',$user_details->id)->where('user_type' , "Rider")->get();
		// $referral_users = ReferralUser::where('user_id',$user_details->id)->whereUserType("rider");

		$pending_referrals = array();
		//$completed_referrals = array();
		// print_r($referral_users);

		foreach ($referral_users as $referral_user) {
			$userww = User::where('id' , $referral_user->referral_id )->first();

			if($userww->user_type == "Driver") {
				continue;
			}
			$temp_details['id'] 			= $referral_user->id;
			$temp_details['name'] 			= $referral_user->referred_user_name . " " . $userww->last_name;
			$temp_details['profile_image'] 	= $referral_user->referred_user_profile_picture_src;
			$temp_details['start_date'] 	= $referral_user->start_date;
			$temp_details['end_date'] 		= $referral_user->end_date;
			$temp_details['days'] 			= $referral_user->days;
			$temp_details['remaining_days'] = $referral_user->remaining_days;
			$temp_details['trips'] 			= $referral_user->trips;
			$temp_details['remaining_trips']= $referral_user->remaining_trips;
			$temp_details['earnable_amount']= $referral_user->earnable_amount;
            $temp_details['status'] 		= ($userww->status == 'Active' ? 'Active' : 'Inactive');
            $temp_details['since']          = date_format($userww->created_at,"M Y");

            $user_home_address = RiderLocation::where('user_id', $userww->id)->first();
            if ($user_home_address && $user_home_address->home != ''){
                $temp_details['location']       = ltrim(array_slice(explode(',', $user_home_address->home), -2, 1)[0]," ");
            }
            else{
                $temp_details['location']       = 'NA';
            }

			//if($referral_user->payment_status == 'Pending') {
				array_push($pending_referrals,$temp_details);
			//}
			//else {
			//	array_push($completed_referrals,$temp_details);
			//}
		}

		return response()->json([
			'status_code' 			=> '1',
			'status_message' 		=> trans('messages.success'),
			'apply_referral' 		=> $admin_referral_settings->value,
			'referral_link' 		=> route('redirect_to_app',['type' => strtolower($user_type)]),
			'referral_code'  		=> $user->referral_code,
			'referral_amount' 		=> $referral_amount,
			'pending_amount' 		=> $user->pending_referral_amount,
			'total_earning'  		=> $user->total_referral_earnings,
			// 'pending_referrals' 	=> $pending_referrals,
            // 'completed_referrals' 	=> $completed_referrals,
            'referrals' 	=> $pending_referrals
		]);
    }

    /**
	 * To Get the referral Users Details
	 * @param  Request $request Get values
	 * @return Response Json
	 */
	public function get_drivers_for_drivers(Request $request)
	{
		$user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

		if ($user == '') {
			return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
		}

		$user_type = $user->user_type;

		$admin_referral_settings = ReferralSetting::whereUserType($user_type)->where('name','apply_referral')->first();

		$referral_amount = 0;
    	if($admin_referral_settings->value) {
        	$referral_amount = $admin_referral_settings->get_referral_amount($user_type);
		}

		$referral_users = ReferralUser::where('user_id',$user_details->id)->get();
		// $referral_users = ReferralUser::where('user_id',$user_details->id)->where('user_type' , "Rider")->get();
		// $referral_users = ReferralUser::where('user_id',$user_details->id)->whereUserType("rider");

		$pending_referrals = array();
		// $completed_referrals = array();
		// print_r($referral_users);

		foreach ($referral_users as $referral_user) {
			$userww = User::where('id' , $referral_user->referral_id )->first();

			if($userww->user_type == "Rider") {
				continue;
            }
            $driver_address = DriverAddress::where('user_id',$referral_user->referral_id)->first();

			$temp_details['id'] 			= $referral_user->id;
			$temp_details['name'] 			= $referral_user->referred_user_name . " " . $userww->last_name;
			$temp_details['profile_image'] 	= $referral_user->referred_user_profile_picture_src;
			$temp_details['start_date'] 	= $referral_user->start_date;
			$temp_details['end_date'] 		= $referral_user->end_date;
			$temp_details['days'] 			= $referral_user->days;
			$temp_details['remaining_days'] = $referral_user->remaining_days;
			$temp_details['trips'] 			= $referral_user->trips;
			$temp_details['remaining_trips']= $referral_user->remaining_trips;
			$temp_details['earnable_amount']= $referral_user->earnable_amount;
            $temp_details['status'] 		= ($userww->status == 'Active' ? 'Active' : 'Inactive');
            $temp_details['since']          = date_format($userww->created_at,"M Y");


            $temp_details['driver_address'] = $driver_address;
            $temp_details['location']       = ucfirst(strtolower($driver_address->city)) . ', ' . $driver_address->state;

			// if($referral_user->payment_status == 'Pending') {
				array_push($pending_referrals,$temp_details);
			// }
			// else {
			// 	array_push($completed_referrals,$temp_details);
			// }
		}

		return response()->json([
			'status_code' 			=> '1',
			'status_message' 		=> trans('messages.success'),
			'apply_referral' 		=> $admin_referral_settings->value,
			'referral_link' 		=> route('redirect_to_app',['type' => strtolower($user_type)]),
			'referral_code'  		=> $user->referral_code,
			'referral_amount' 		=> $referral_amount,
			'pending_amount' 		=> $user->pending_referral_amount,
			'total_earning'  		=> $user->total_referral_earnings,
			'referrals' 	        => $pending_referrals

		]);
	}

	/**
     * Import GrowSurf participants
     *
     * @return \Illuminate\Http\Response
     */
    public function importParticipants()
    {
		try {
			// $getUserList = DB::table('users')->selectRaw('id as user_id,first_name,last_name,email,growsurf_id,used_referral_code,user_type')->whereNotIn('user_type',['riders'])->where('email','<>','')->groupBy('email')->get()->toArray();
			$getUserList = DB::table('users as u')
			->selectRaw('u.id as user_id,u.first_name,u.last_name,u.email,u.growsurf_id,u.referred_by,u.user_type')
			->join('stripe_subscriptions as s','s.user_id','u.id')
			->where('u.user_type','Driver')
			->where('u.email','<>','')
			->where('s.plan',2)
			->groupBy('u.email')->get()->toArray();
			$lastElement = end($getUserList);

			$referral_service = resolve('App\Services\ReferralPrograms\GrowSurf');
			if(sizeof($getUserList) > 0) {
				foreach($getUserList as $key=>$user) {
					$response = $referral_service->createUser([
						'firstName' => $user->first_name,
						'lastName' => $user->last_name,
						'email' => $user->email
					]);
					if(!isset($response->errors) && !empty($response)) {
						// DB::table('users')->where('used_referral_code',$user->growsurf_id)->update(['used_referral_code' => $response->id]);
						DB::table('users')->where('id',$user->user_id)->update(['growsurf_id' => $response->id]);
					}
					// watch($key);
					if($user == $lastElement){break;}
				}
				return response()->json([
					'status_code' 		=> '1',
					'status_message' 	=> "Participants importd successfully."
				]);
			} else {
				return response()->json([
					'status_code' 		=> '0',
					'status_message' 	=> "No Participant found."
				]);
			}
		} catch(\Exception $e) { errorLog($e); }
	}

	/**
     * Update GrowSurf participants
     *
     * @return \Illuminate\Http\Response
     */
    public function updateParticipant(Request $request)
    {
		try {
			$rules = array(
				'particiapnt_id' => 'required',
				'referred_by' => 'required',
			);
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return response()->json([
					'status_code'    => '0',
					'status_message' => $validator->messages()->first(),
				]);
			}
			$referral_service = resolve('App\Services\ReferralPrograms\GrowSurf');
			$response = $referral_service->updateUser(['referredBy' => $request->referred_by],$request->particiapnt_id);
			if(!isset($response->errors) && !empty($response)) {
				DB::table('users')->where('growsurf_id',$request->particiapnt_id)->update(['referred_by' => $request->referred_by]);
			}
			return response()->json([
				'status_code' 		=> '1',
				'status_message' 	=> "Participants updated successfully.",
				'response_data' 	=> $response
			]);
		} catch(\Exception $e) { errorLog($e); }
	}

	/**
     * Get all GrowSurf referrals of referrer
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllReferralsById($referred_by)
    {
		try {
			$referralList = DB::table('users as u')
			->selectRaw('u.id as user_id,u.first_name,u.last_name,u.email,u.growsurf_id,u.referred_by,u.user_type,s.plan')
			->leftjoin('stripe_subscriptions as s','s.user_id','u.id')
			->where('u.referred_by',$referred_by)->get();
			if(sizeof($referralList) > 0) {
				foreach($referralList as $value) {
					if($value->plan == 3 || $value->plan == 4 || $value->plan == 5) {
						$value->user_type = 'community_leader';
					}
				}
			}
			return response()->json([
				'status_code' 		=> '1',
				'status_message' 	=> "Referrals get successfully.",
				'response_data' 	=> $referralList
			]);
		} catch(\Exception $e) { errorLog($e); }
	}

	/**
     * Get GrowSurf referrer by referral id
     *
     * @return \Illuminate\Http\Response
     */
    public function getReferrerById($referral_id)
    {
		try {
			$getId = DB::table('users')->selectRaw('referred_by')->where('growsurf_id',$referral_id)->get()->pluck('referred_by')->toArray();
			$getReferrer = DB::table('users as u')
			->selectRaw('u.id as user_id,u.first_name,u.last_name,u.email,u.growsurf_id,u.referred_by,u.user_type,s.plan')
			->leftjoin('stripe_subscriptions as s','s.user_id','u.id')
			->whereIn('u.growsurf_id',$getId)->get();
			if(sizeof($getReferrer) > 0) {
				foreach($getReferrer as $value) {
					if($value->plan == 3 || $value->plan == 4 || $value->plan == 5) {
						$value->user_type = 'community_leader';
					}
				}
			}
			return response()->json([
				'status_code' 		=> '1',
				'status_message' 	=> "Referrer get successfully.",
				'response_data' 	=> $getReferrer
			]);
		} catch(\Exception $e) { errorLog($e); }
    }
}