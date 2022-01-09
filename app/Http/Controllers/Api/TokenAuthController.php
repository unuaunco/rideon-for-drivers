<?php

/**
 * Token Auth Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Token Auth
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Country;
use App\Models\ProfilePicture;
use App\Models\DriverLocation;
use App\Models\DriverAddress;
use App\Models\HomeDeliveryOrder;
use App\Models\CarType;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Trips;
use App\Models\Language;
use App\Models\PaymentMethod;
use App\Models\DriversSubscriptions;
use App\Models\StripeSubscriptionsPlans;
use App\Models\BadOrders;
use App\Models\Request as RideRequest;
use Validator;
use Session;
use App;
use JWTAuth;
use Auth;
use DB;
use Mail;

class TokenAuthController extends Controller
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
     * Get User Details
     * 
     * @param Collection User
     *
     * @return Response Array
     */
    protected function getUserDetails($user)
    {
        $invoice_helper = resolve('App\Http\Helper\InvoiceHelper');
        $promo_details = $invoice_helper->getUserPromoDetails($user->id);

        $user_data = array(
            'user_id'           => $user->id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'mobile_number'     => $user->mobile_number,
            'country_code'      => $user->country_code,
            'email_id'          => $user->email ?? '',
            'user_status'       => $user->status,
            'user_thumb_image'  => @$user->profile_picture->src ?? url('images/user.jpeg'),
            'currency_symbol'   => $user->currency->symbol,
            'currency_code'     => $user->currency->code,
            'payout_id'         => $user->payout_id ?? '',
            'wallet_amount'     => getUserWalletAmount($user->id),
            'promo_details'     => $promo_details,
        );

        // Also sent for rider because mobile team also handle these parameters in rider

        $rider_details = array();
        if($user->user_type == 'Rider' || true) {
            $user->load('rider_location');
            $rider_location = $user->rider_location;
            $rider_details = array(
                'home'          => optional($rider_location)->home ?? '',
                'work'          => optional($rider_location)->work ?? '',
                'home_latitude' => optional($rider_location)->home_latitude ?? '',
                'home_longitude'=> optional($rider_location)->home_longitude ?? '',
                'work_latitude' => optional($rider_location)->work_latitude ?? '',
                'work_longitude'=> optional($rider_location)->work_longitude ?? '',
                'rider_rating'  => getRiderRating($user->id),
            );
        }

        $driver_details = array();
        if($user->user_type == 'Driver' || true) {
            $user->load(['driver_documents','driver_address']);
            $driver_documents = $user->driver_documents;
            $driver_address = $user->driver_address;
            $driver_details = array(
                'car_details'       => CarType::active()->get(),
                'license_front'     => optional($driver_documents)->license_front ?? '',
                'license_back'      => optional($driver_documents)->license_back ?? '',
                'insurance'         => optional($driver_documents)->insurance ?? '',
                'rc'                => optional($driver_documents)->rc ?? '',
                'permit'            => optional($driver_documents)->permit ?? '',
                'vehicle_id'        => optional($driver_documents)->vehicle_id ?? '',
                'vehicle_type'      => optional($driver_documents)->vehicle_type ?? '',
                'vehicle_number'    => optional($driver_documents)->vehicle_number ?? '',
                'address_line1'     => optional($driver_address)->address_line1 ?? '',
                'address_line2'     => optional($driver_address)->address_line2 ?? '',
                'state'             => optional($driver_address)->state ?? '',
                'postal_code'       => optional($driver_address)->postal_code ?? '',
                'company_name'      => $user->company_name,
                'company_id'        => $user->company_id ?? '',
                'driver_rating'     => getDriverRating($user->id),
            );
        }

        return array_merge($user_data,$rider_details,$driver_details);
    }
 
    /**
     * User Resister
     *@param  Get method request inputs
     *
     * @return Response Json 
     */
    public function register(Request $request) 
    {
        $language = $request->language ?? 'en';
        App::setLocale($language);

        $rules = array(
            'mobile_number'   => 'required|regex:/^[0-9]+$/|min:6',
            'user_type'       => 'required|in:Rider,Driver,rider,driver',
            'auth_type'       => 'required|in:facebook,google,apple,email',
            'email_id'        => 'required|max:255|email',
            'password'        => 'required|min:6',
            'first_name'      => 'required',
            'last_name'       => 'required',
            'country_code'    => 'required',
            'device_type'     => 'required',
            'device_id'       => 'required',
            'referral_code'   => 'nullable|exists:users,referral_code',
        );

        if(strtolower($request->user_type) == 'driver') {
            $rules['city'] = 'required';
        }

        if(in_array($request->auth_type,['facebook','google','apple'])) {
            $social_signup = true;
            $rules['auth_id'] = 'required';
        }
       
        $attributes = array(
            'mobile_number' => trans('messages.user.mobile'),
            'referral_code' => trans('messages.referrals.referral_code'), 
        );

        $messages = array(
            'referral_code.exists'  => trans('messages.referrals.enter_valid_referral_code'),
        );

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        if($validator->fails())  {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }

        //$referral_check = User::whereUserType(ucfirst($request->user_type))->where('referral_code',$request->referral_code)->count();
        $referral_check = User::get()->where('referral_code',$request->referral_code)->count();
        if($request->referral_code != '' && $referral_check == 0) {
            return response()->json([
                'status_code' => '0',
                'status_message' => __('messages.referrals.enter_valid_referral_code')
            ]);
        }

        $mobile_number = $request->mobile_number;
        $user_count = User::where('mobile_number', $mobile_number)->where('user_type', $request->user_type)->count();
        if($user_count > 0) {
            return response()->json([
                'status_code'     => '0',
                'status_message' =>  trans('messages.already_have_account'),
            ]);
        }

        $user_email_count = User::where('email', $request->email_id)->where('user_type', $request->user_type)->count();
        if($user_email_count > 0) {
            return response()->json([
                'status_code'     => '0',
                'status_message' =>  trans('messages.api.email_already_exists'),
            ]);
        }
        
        $user = new User;
        $user->mobile_number    =   $request->mobile_number;
        $user->first_name       =   $request->first_name;
        $user->last_name        =   $request->last_name;
        $user->user_type        =   $request->user_type;
        $user->password         =   $request->password;
        $user->country_code     =   $request->country_code;
        $user->device_type      =   $request->device_type;
        $user->device_id        =   $request->device_id;
        $user->language         =   $language;
        $user->email            =   $request->email_id;
        $user->currency_code    =   get_currency_from_ip();
        $user->used_referral_code = $request->referral_code;

        if(strtolower($request->user_type) =='rider') {
            return response()->json([
                'status_code'    => '0',
                'status_message' => 'Sorry, Ride shares not accessible right now.',
            ]);
            $user->status           =   "Active";
            if(isset($social_signup)) {
                if($request->auth_type == 'facebook') {
                    $auth_column = 'fb_id';
                }
                else if($request->auth_type == 'google') {
                    $auth_column = 'google_id';
                }
                else {
                    $auth_column = 'apple_id';
                }

                $user->$auth_column = $request->auth_id;

                $photo_source = ucfirst($request->auth_type);
                $image = $request->user_image ?? '';
            }           

            $user->save();                  
        }
        else {
            $user->company_id       =   1;
            $user->status           =   "Car_details";
            $user->save();
            $driver_address                    = new DriverAddress;
            $driver_address->user_id           = $user->id;
            $driver_address->address_line1     = '';
            $driver_address->address_line2     = '';
            $driver_address->city              = $request->city;
            $driver_address->state             = '';
            $driver_address->postal_code       = '';
            $driver_address->save();

            $plan = StripeSubscriptionsPlans::where('plan_name','Driver only')->first();
            $subscription_row = new DriversSubscriptions;
            $subscription_row->user_id      = $user->id;
            $subscription_row->stripe_id    = '';
            $subscription_row->status       = 'subscribed';
            $subscription_row->email        = $user->email;
            $subscription_row->plan         = $plan->id;
            $subscription_row->country      = '';
            $subscription_row->card_name    = '';   
            $subscription_row->save(); 
        }

        $profile               = new ProfilePicture;
        $profile->user_id      = $user->id;
        $profile->src          = $image ?? '';
        $profile->photo_source = $photo_source ?? 'Local';
        $profile->save();

        $credentials = $request->only('mobile_number', 'password','user_type');
     
        try {
            $token = JWTAuth::attempt($credentials);
            if (!$token) {
                return response()->json(['error' => 'invalid_credentials']);
            }
        }
        catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token']);
        }

        $return_data = array(
            'status_code'       => '1',
            'status_message'    => __('messages.user.register_successfully'),
            'access_token'      => $token,
        );

        $user_data = $this->getUserDetails($user);

        return response()->json(array_merge($return_data,$user_data));
    }

    /**
     * User Socail media Resister & Login 
     * @param Get method request inputs
     *
     * @return Response Json 
     */
    public function apple_callback(Request $request) 
    {
        $client_id = api_credentials('service_id','Apple');

        $client_secret = getAppleClientSecret();

        $params = array(
            'grant_type' 	=> 'authorization_code',
            'code' 		 	=> $request->code,
            'redirect_uri'  => url('api/apple_callback'),
            'client_id' 	=> $client_id,
            'client_secret' => $client_secret,
        );
        
        $curl_result = curlPost("https://appleid.apple.com/auth/token",$params);

        if(!isset($curl_result['id_token'])) {
            $return_data = array(
                'status_code'       => '0',
                'status_message'    => $curl_result['error'],
            );

            return response()->json($return_data);
        }

        $claims = explode('.', $curl_result['id_token'])[1];
        $user_data = json_decode(base64_decode($claims));

        $user = User::where('apple_id', $user_data->sub)->first();

        if($user == '') {
            $return_data = array(
                'status_code'       => '1',
                'status_message'    => 'New User',
                'email_id'          => optional($user_data)->email ?? '',
                'apple_id'          => $user_data->sub,
            );

            return response()->json($return_data);
        }

        $token = JWTAuth::fromUser($user);

        $user_details = $this->getUserDetails($user);

        $return_data = array(
            'status_code'       => '2',
            'status_message'    => 'Login Successfully',
            'apple_email'       => optional($user_data)->email ?? '',
            'apple_id'          => $user_data->sub,
            'access_token'      => $token,
        );

        return response()->json(array_merge($return_data,$user_details));
    }

    /**
     * User Socail media Resister & Login 
     * @param Get method request inputs
     *
     * @return Response Json 
     */
    public function socialsignup(Request $request) 
    {
        $rules = array(
            'auth_type'   => 'required|in:facebook,google,apple',
            'auth_id'     => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }

        if($request->auth_type == 'facebook') {
            $auth_column = 'fb_id';
        }
        else if($request->auth_type == 'google') {
            $auth_column = 'google_id';
        }
        else {
            $auth_column = 'apple_id';
        }

        $user_count = User::where($auth_column,$request->auth_id)->count();

        // Social Login Flow
        if($user_count == 0) {
            return response()->json([
                'status_code'   => '2',
                'status_message'=> 'New User',
            ]);
        }

        $rules =  array(
            'device_type'  =>'required',
            'device_id'    =>'required'
        );

        $messages = array('required'=>':attribute is required.');
        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }

        $user = User::where($auth_column,$request->auth_id)->first();

        $user->device_id    = $request->device_id;
        $user->device_type  = $request->device_type;
        $user->language     = $request->language;

        $user->currency_code= get_currency_from_ip();
        $user->save();

        $token = JWTAuth::fromUser($user);

        $return_data = array(
            'status_code'       => '1',
            'status_message'    => 'Login Success',
            'access_token'      => $token,
        );

        $user_data = $this->getUserDetails($user);

        return response()->json(array_merge($return_data,$user_data));
    }

    /**
     * User Login
     * @param  Get method request inputs
     *
     * @return Response Json 
     */
    public function login(Request $request)
    {
        $user_id = $request->mobile_number;
        $auth_column   = 'mobile_number';

        $rules = array(
            'mobile_number'   =>'required|regex:/^[0-9]+$/|min:6',
            'user_type'       =>'required|in:Rider,Driver,rider,driver',
            'password'        =>'required',
            'country_code'    =>'required',
            'device_type'     =>'required',
            'device_id'       =>'required',
           // 'language'        =>'required',
        );

        $validator = Validator::make($request->all(), $rules); 

        if($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }

        $language = $request->language ?? 'en';
        App::setLocale($language);

        $attempt = Auth::attempt([$auth_column => $user_id, 'password' => $request->password,'user_type' =>$request->user_type]);

        if(!$attempt) {
            return response()->json([
                'status_code'    => '0',
                'status_message' => __('messages.credentials'),
            ]);
        }

        $credentials = $request->only($auth_column, 'password','user_type');
    
        try {
            $token = JWTAuth::attempt($credentials);
            if (!$token) {
                return response()->json([
                    'status_code'    => '0',
                    'status_message' => __('messages.credentials'),
                ]);
            }

        }
        catch (JWTException $e) {
            return response()->json([
                'status_code'    => '0',
                'status_message' => 'could_not_create_token',
            ]);
        }

        $user = User::with('company')->where($auth_column, $user_id)->where('user_type',$request->user_type)->first();

        if($user->status == 'Inactive') {
            return response()->json([
                'status_code'     => '0',
                'status_message' => __('messages.inactive_admin'),
           ]);
        }

        if(isset($user->company) && $user->company->status == 'Inactive') {
            return response()->json([
                'status_code'     => '0',
                'status_message' => __('messages.inactive_company'),
           ]);
        }

        $currency_code          = get_currency_from_ip();
        User::whereId($user->id)->update([
            'device_id'     => $request->device_id,
            'device_type'   => $request->device_type,
            'currency_code' => $currency_code,
            'language'=>$request->language
        ]);

        $user = User::where('id', $user->id)->first();
        auth()->setUser($user);

        if(strtolower($request->user_type) != 'rider') {
            $first_car = CarType::active()->first();
            $data = [   
                'user_id'  => $user->id,
                'status'   => 'Offline',
                'car_id'   => optional($user->driver_documents)->vehicle_id ?? $first_car->id,
            ];

            DriverLocation::updateOrCreate(['user_id' => $user->id], $data);
            RideRequest::where('driver_id',$user->id)->where('status','Pending')->update(['status'=>'Cancelled']);
        }
        else{
            return response()->json([
                'status_code'    => '0',
                'status_message' => 'Sorry, Ride shares not accessible right now',
            ]);
        }

        $language = $user->language ?? 'en';
        App::setLocale($language);

        $return_data = array(
            'status_code'       => '1',
            'status_message'    => __('messages.login_success'),
            'access_token'      => $token,
        );

        $user =$this->getUserDetails($user);
    
        return response()->json(array_merge($return_data,$user));   
    }

    public function language(Request $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $user= User::find($user_details->id);

        if($user == '') {
            return response()->json([
                'status_code'    => '0',
                'status_message' => __('messages.invalid_credentials'),
            ]);
        }
        $user->language = $request->language;
        $user->save();

        $language = $user->language ?? 'en';

        App::setLocale($language);

        return response()->json([
            'status_code'       => '1',
            'status_message'    => trans('messages.update_success'),
        ]);
    }
    
     /**
     * User Email Validation
     *
     * @return Response in Json
     */
    public function emailvalidation(Request $request)
    {
        $rules = array('email'=> 'required|max:255|email_id|unique:users');

        // Email signup validation custom messages
        $messages = array('required'=>':attribute is required.');

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails()) {
            return response()->json([
                'status_code'   => '0',
                'status_message'=> 'Email Already exist',
            ]);
        }

        return response()->json([
            'status_code'   => '1',
            'status_message'=> 'Emailvalidation Success',
        ]);
    }

    /**
     * Forgot Password
     * 
     * @return Response in Json
     */ 
    public function forgotpassword(Request $request)
    {
        $rules = array(
            'mobile_number'   => 'required|regex:/^[0-9]+$/|min:6',
            'user_type'       =>'required|in:Rider,Driver,rider,driver',
            'password'        =>'required|min:6',
            'country_code'    =>'required',
            'device_type'     =>'required',
            'device_id'       =>'required'
        );
        $attributes = array(
            'mobile_number'   => 'Mobile Number',
        );

        $validator = Validator::make($request->all(), $rules, $attributes);

        if($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }
        $user_check = User::where('mobile_number', $request->mobile_number)->where('user_type', $request->user_type)->first();
        
        if($user_check == '') {
            return response()->json([
                'status_code'    => '0',
                'status_message' => __('messages.invalid_credentials'),
            ]);
        }

        $user = User::whereId($user_check->id)->first();
        $user->password = $request->password;
        $user->device_id = $request->device_id;
        $user->device_type = $request->device_type;
        $user->currency_code = $request->currency_code;
        $user->save();

        $user = User::where('mobile_number', $request->mobile_number)->where('user_type', $request->user_type)->first();

        $token = JWTAuth::fromUser($user);

        auth()->setUser($user);

        if(strtolower($request->user_type) != 'rider') {
            $data = [
                'user_id'  => $user->id,
                'status'   => 'Offline',
                'car_id'   => @$user->driver_documents->vehicle_id!=''? $user->driver_documents->vehicle_id:1
            ];
            DriverLocation::updateOrCreate(['user_id' => $user->id], $data);
            RideRequest::where('driver_id',$user->id)->where('status','Pending')->update(['status'=>'Cancelled']);
        }

        $return_data = array(
            'status_code'       => '1',
            'status_message'    => __('messages.login_success'),
            'access_token'      => $token,
        );

        $user_data =$this->getUserDetails($user);
        
        return response()->json(array_merge($return_data,$user_data));
    }

    /**
     * Mobile number verification
     * 
     * @return Response in Json
     */ 
    public function numbervalidation(Request $request)
    {
        if(isset($request->language)) {
            $language = $request->language;
        }
        else {
            $language = 'en';
        }
        App::setLocale($language);

        $rules = array(
            'mobile_number'   => 'required|regex:/^[0-9]+$/|min:6',
            'user_type'       =>'required|in:Rider,Driver,rider,driver',
            'country_code'    =>'required',
        );

        if($request->forgotpassword==1) {
            $rules['mobile_number'] = 'required|regex:/^[0-9]+$/|min:6|exists:users,mobile_number';
        }

        $messages = array(
            'mobile_number.required' => trans('messages.mobile_num_required'),
            'mobile_number.exists'   => trans('messages.enter_registered_number'),
        );

        $validator = Validator::make($request->all(), $rules,$messages);
      
        if($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }

        $mobile_number = $request->mobile_number;

        $user = User::where('mobile_number', $mobile_number)->where('user_type', $request->user_type)->get();
        if($user->count() && $request->forgotpassword != 1) {
            return response()->json([
                'status_message'  => trans('messages.mobile_number_exist'),
                'status_code'     => '0',
            ]);
        }

        if($user->count() <= 0 && $request->forgotpassword == 1) {
            return response()->json([
                'status_message'  => trans('messages.number_does_not_exists'),
                'status_code'     => '0',
            ]);
        }

        $otp = rand(1000,9999);
        $text = __('messages.api.your_otp_is').$otp;
        $to = '+'.$request->country_code.$request->mobile_number;
        $twillio_responce = $this->request_helper->send_message($to,$text);

        /*if($twillio_responce['status_code'] == 0) {
            return response()->json([
                'status_message' => $twillio_responce['message'],
                'status_code' => '0',
                'otp' => '',
            ]);
        }*/

        return response()->json([
            'status_code'    => '1',
            'status_message' => 'Success',
            'otp'           => strval($otp),
        ]);
    }

    /**
     * Updat Device ID and Device Type
     * @param  Get method request inputs
     *
     * @return Response Json 
     */
    public function updateDevice(Request $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $rules = array(
            'user_type'    =>'required|in:Rider,Driver,rider,driver',
            'device_type'  =>'required',
            'device_id'    =>'required'
        );
        $attributes = array(
            'mobile_number'   => 'Mobile Number',
        );
        $validator = Validator::make($request->all(), $rules, $attributes);

        if($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }

        $user = User::where('id', $user_details->id)->first();

        if($user == '') {
            return response()->json([
                'status_code'       => '0',
                'status_message'    => trans('messages.api.invalid_credentials'),
            ]);
        }

        User::whereId($user_details->id)->update(['device_id'=>$request->device_id,'device_type'=>$request->device_type]);                
        return response()->json([
            'status_code'     => '1',
            'status_message'  => __('messages.api.updated_successfully'),
        ]);
    }

    public function logout(Request $request)
    {
        $user_details = JWTAuth::parseToken()->authenticate();

        $user = User::where('id', $user_details->id)->first();

        if($user == '') {
            return response()->json([
                'status_code'       => '0',
                'status_message'    => __('messages.api.invalid_credentials'),
            ]);
        }

        if($user->user_type == 'Driver') {

            $trips_count = Trips::where('driver_id',$user_details->id)->whereNotIn('status',['Completed','Cancelled'])->count();

            $driver_location = DriverLocation::where('user_id',$user_details->id)->first();

            if(optional($driver_location)->status == 'Trip' || $trips_count > 0) {
                return response()->json([
                    'status_code'    => '0',
                    'status_message' => __('messages.complete_your_trips'),
                ]); 
            }

            DriverLocation::where('user_id',$user_details->id)->update(['status'=>'Offline']);
            JWTAuth::invalidate($request->token);
            Session::flush();

            $user->device_type = Null;
            $user->device_id = '';
            $user->save();
            
            return response()->json([
                'status_code'     => '1',
                'status_message'  => "Logout Successfully",
            ]); 
        }

        $trips_count = Trips::where('user_id',$user_details->id)->whereNotIn('status',['Completed','Cancelled'])->count();
        if($trips_count) {
            return response()->json([
              'status_code'    => '0',
              'status_message' => __('messages.complete_your_trips'),
            ]);
        }
        //Deactive the Access Token
        JWTAuth::invalidate($request->token);

        Session::flush();

        $user->device_type = Null;
        $user->device_id = '';
        $user->save();

        return response()->json([
            'status_code'     => '1',
            'status_message'  => "Logout Successfully",
        ]);
    }

    public function currency_conversion(Request $request)
    {
        $rules  = [
            'amount' => 'required|numeric|min:0'
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return response()->json([
                'status_code' => '0',
                'status_message' => $validator->messages()->first()
            ]);
        }

        $user_details   = JWTAuth::toUser($request->token);
        $currency_code  = $user_details->currency->code;
        $payment_currency = site_settings('payment_currency');

        $price = floatval($request->amount);

        $converted_amount = currencyConvert($currency_code,$payment_currency,$price);

        $gateway = resolve('braintree');
        $customer_id = $user_details->id.$user_details->mobile_number;
        try {
            $customer = $gateway->customer()->find($customer_id);
        }
        catch(\Exception $e) {
            try {
                $newCustomer = $gateway->customer()->create([
                    'id'        => $customer_id,
                    'firstName' => $user_details->first_name,
                    'lastName'  => $user_details->last_name,
                    'email'     => $user_details->email,
                    'phone'     => $user_details->phone_number,
                ]);
            }
            catch(\Exception $e) {
                if($e instanceOf \Braintree\Exception\Authentication) {
                    return response()->json([
                        'status_code' => '0',
                        'status_message' => __('messages.api.authentication_failed'),
                    ]);
                }
                return response()->json([
                    'status_code' => '0',
                    'status_message' => $e->getMessage(),
                ]);
            }
            $customer = $newCustomer->customer;
        }

        $bt_clientToken = $gateway->clientToken()->generate([
            "customerId" => $customer->id
        ]);

        return response()->json([
            'status_code'    => '1',
            'status_message' => 'Amount converted successfully',
            'currency_code'  => $payment_currency,
            'amount'         => $converted_amount,
            'braintree_clientToken' => $bt_clientToken,
        ]);
    }

    public function getSessionOrDefaultCode()
    {
        $currency_code = Currency::defaultCurrency()->first()->code;
    }

    public function currency_list() 
    {
        $currency_list = Currency::active()->orderBy('code')->get();
        $curreny_list_keys = ['code', 'symbol'];

        $currency_list = $currency_list->map(function ($item, $key) use($curreny_list_keys) {
            return array_combine($curreny_list_keys, [$item->code, $item->symbol]);
        })->all();

        if(!empty($currency_list)) { 
            return response()->json([
                'status_message' => 'Currency Details Listed Successfully',
                'status_code'     => '1',
                'currency_list'   => $currency_list
            ]);
        }
        return response()->json([
            'status_code'     => '0',
            'status_message' => 'Currency Details Not Found',
        ]);
    }

    public function language_list() 
    {
        $languages = Language::active()->get();

        $languages = $languages->map(function ($item, $key)  {
            return $item->value;
        })->all();

        if(!empty($languages)) { 
            return response()->json([
                'status_code'   => '1',
                'status_message'=> 'Successfully',
                'language_list' => $languages,
            ]);
        }
        return response()->json([
            'status_code'     => '0',
            'status_message' => 'language Details Not Found',
        ]);
    }

    /**
     * Webhook for integration with GloriaFood 
     * @param Get method request inputs
     *
     * @return Response Json 
     */
    public function gloria_food(Request $request) 
    {
        if($request->isMethod("POST")) {
            
            $server_key = $request->header("Authorization");

            // !--- need to change this to asymmetric crypto checking
            $merchant_check = Merchant::where('shared_secret', $server_key)->count();              
            
            if ($merchant_check > 0){
                try{
                    $merchant_id = Merchant::where('shared_secret', $server_key)->first()->id;

                    foreach($request->orders as $order){
                                            
                        // -- Location data
                        $data['pick_up_latitude'] = $order["restaurant_latitude"] ? $order["restaurant_latitude"] : '0.00';
                        $data['pick_up_longitude'] = $order["restaurant_longitude"] ? $order["restaurant_longitude"] : '0.00';
                        $data['pick_up_location'] = $order["restaurant_street"] . ' ' . $order["restaurant_city"];
                        $data['drop_off_longitude'] = $order["longitude"] ? $order["longitude"] : '0.00';
                        $data['drop_off_latitude'] = $order["latitude"] ? $order["latitude"] : '0.00';
                        $data['drop_off_location'] = $order["client_address"];

                        try{
                            if($data['drop_off_latitude'] == '0.00'){
                                $dropoff_geocode = $this->request_helper->GetLatLng($order["client_address"]);
                                $data['drop_off_longitude'] = $dropoff_geocode[1];
                                $data['drop_off_latitude'] = $dropoff_geocode[0];
                            }
                            else if($data['pick_up_latitude'] == '0.00'){
                                $pickup_geocode = $this->request_helper->GetLatLng($data['pick_up_location']);
                                $data['pick_up_latitude'] = $pickup_geocode[1];
                                $data['pick_up_longitude'] = $pickup_geocode[0];
                            }
                        }
                        catch(\Exception $e){
                            //
                        }

                        // --- Customer data
                        // --- Cut australian code from client mobile number
                        $data['country_code'] = "61";
                        $data['mobile_number'] = ltrim($order["client_phone"], "+".$data['country_code']);

                        $data['first_name'] = $order["client_first_name"];
                        $data['last_name'] = $order["client_last_name"];
                        $data['email'] = $order["client_email"];
                        
                        $data['delivery_fee'] = null;
                        
                        try {
                            if ($order["items"][0]["name"] == "DELIVERY_FEE"){
                                $data['delivery_fee'] = $order["items"][0]["total_item_price"];
                            }
                        } 	catch (\Exception $e) {
                            logger('getting delivery fee error : '.$e->getMessage());
                        }

                        $user = $this->get_or_create_rider((object)$data);
                        

                        $ride_request = $this->create_ride_request((object)$data, $user);

                        $merchant = Merchant::where('id', $merchant_id)->first();

                        $existance_order = HomeDeliveryOrder::whereNotIn('delivery_orders.status', ['delivered'])->where('delivery_orders.merchant_id', $merchant_id)->join('request', 'delivery_orders.ride_request', '=', 'request.id' )->where('request.drop_location', $data['drop_off_location'])->count();

                        if(!$existance_order){
                            //create order
                            $new_order = new HomeDeliveryOrder;

                            $accepted_time = new \Carbon\Carbon($order["accepted_at"]);
                            $fulfill_time = new \Carbon\Carbon($order["fulfill_at"]);
                            
                            try{
                                $restaurant_timezone = $order["restaurant_timezone"];
                            }
                            catch(\Exception $e){
                                //
                            }
                            $est_time = $fulfill_time->diffInMinutes($accepted_time);
                            try{

                                if($est_time > 60){
                                    $new_order->status = 'pre_order';
                                }
                                $est_time = $est_time - 15;
                            }
                            catch(\Exception $e){
                                //
                            }

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
                            
                            $new_order->distance                = $get_fare_estimation['distance'];
                            $new_order->estimate_time           = $est_time;
                            $new_order->fee                     = 0;
                            $new_order->customer_id             = $user->id;
                            $new_order->ride_request            = $ride_request->id;
                            
                            $new_order->merchant_id             = $merchant_id;

                            try{
                                $new_order->order_description       = $order["instructions"];
                            }
                            catch(\Exception $e){
                                $new_order->order_description       = $order["instructions"];
                            }
                            $fee = 0.0;
                            // if($data['delivery_fee']){
                            //     $fee = $data['delivery_fee'];
                            // }
                            // else{
                            if(($get_fare_estimation['distance']/1000) > $merchant->delivery_fee_base_distance){
                                $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance']/1000 - $merchant->delivery_fee_base_distance);
                            }
                            else{
                                $fee = $merchant->delivery_fee;
                            }
                            //}
                            $new_order->fee                     = round($fee, 2);
                            
                            $new_order->save();
                            logger($new_order->id . ' fulfill time : ' . $order["fulfill_at"]);
                            $this->notify_drivers((object)$data, 'New job(s) in your location');

                            return response()->json([
                                'status_code'     => '1',
                                'status_message' => 'Successfully created',
                            ]);
                        }
                        else{
                            return response()->json([
                                'status_code'     => '1',
                                'status_message' => 'Already created',
                            ]);
                        }
                    }
                }
                catch(\Exception $e){
                    $data = $request->all();
                    //in case of bad order data 
                    //put that order information 
                    //in db to manual inserting
                    $bad_order = new BadOrders;
                    $bad_order->secret = $server_key;
                    $bad_order->description = json_encode($data);
                    $bad_order->save();

                    $merchant_name = Merchant::where('shared_secret', $server_key)->first()->name;

                    $emails      = ['pardusurbanus@protonmail.com'];
                    $content    = [
                        'first_name' => '_'
                    ];
                    $data['content'] = json_encode($data, JSON_PRETTY_PRINT);
                    $data['first_name'] = $content['first_name'];
                    $data['merchant'] = $merchant_name;
                    // Send Forgot password email to give user email
                    foreach($emails as $email){
                        Mail::send('emails.bad_order', $data, function($message) use ($email, $content){
                            $message->to($email, $content['first_name'])->subject('Ride On New bad data order');
                            $message->from('api@rideon.group','Ride on Tech support');
                        });
                    }

                    return array(
                        'status' => false,
                        'status_message' => $e->getMessage(),
                    );
                }
            }
        }
    }

        /**
     * Webhook for integration with various systems 
     * @param Get method request inputs
     *
     * @return Response Json 
     */
    public function general_orders_api(Request $request) 
    {   
        $server_key = $request->header("Authorization");
        if(!$server_key){
            return response()->json(['status' => 'Unauthorized'], 401);
        }
        
        $merchant_check = Merchant::where('shared_secret', $server_key)->count(); 
        if(!$merchant_check) {
			return response()->json(['status' => 'Token is Invalid'], 401);
		}

        if($request->isMethod("POST")) {
            try{
                $merchant_id = Merchant::where('shared_secret', $server_key)->first()->id;

                $new_orders = array();

                foreach($request->orders as $order){

                    // -- Location data
                    $data['pick_up_latitude'] = $order["restaurant_location"]["restaurant_latitude"] ? $order["restaurant_location"]["restaurant_latitude"] : '0.00';
                    $data['pick_up_longitude'] = $order["restaurant_location"]["restaurant_longitude"] ? $order["restaurant_location"]["restaurant_longitude"] : '0.00';
                    $data['pick_up_location'] = $order["restaurant_location"]["restaurant_address"];
                    $data['restaurant_timezone'] = $order["restaurant_location"]["restaurant_timezone"];

                    $data['drop_off_location'] = $order["fulfillment_details"]["drop_address"] ? $order["fulfillment_details"]["drop_address"] : '0.00';
                    $data['drop_off_latitude'] = $order["fulfillment_details"]["drop_latitude"] ? $order["fulfillment_details"]["drop_latitude"] : '0.00';
                    $data['drop_off_longitude'] = $order["fulfillment_details"]["drop_longitude"] ? $order["fulfillment_details"]["drop_longitude"] : '0.00';

                    try{
                        if($data['drop_off_latitude'] == '0.00'){
                            $dropoff_geocode = $this->request_helper->GetLatLng($order["client_address"]);
                            $data['drop_off_longitude'] = $dropoff_geocode[1];
                            $data['drop_off_latitude'] = $dropoff_geocode[0];
                        }
                        if($data['pick_up_latitude'] == '0.00'){
                            $pickup_geocode = $this->request_helper->GetLatLng($data['pick_up_location']);
                            $data['pick_up_latitude'] = $pickup_geocode[0];
                            $data['pick_up_longitude'] = $pickup_geocode[1];
                        }
                    }
                    catch(\Exception $e){
                        logger('getting locations error : '.$e->getMessage());
                    }

                    // --- Customer data
                    // --- Cut australian code from client mobile number
                    $data['country_code'] = "61";
                    $data['mobile_number'] = ltrim($order["cutomer_data"]["customer_phone_number"], "+".$data['country_code']);

                    $data['first_name'] = $order["cutomer_data"]["customer_first_name"];
                    $data['last_name'] = $order["cutomer_data"]["customer_last_name"];
                    $data['email'] = $order["cutomer_data"]["customer_email"];
                    
                    $data['delivery_fee'] = null;
                    
                    try {
                        $data['delivery_fee'] = $order["fulfillment_details"]["delivery_fee"] ? $order["fulfillment_details"]["delivery_fee"] : null;
                        $data['delivery_fee_currency'] = $order["fulfillment_details"]["delivery_fee_currency"];
                    }
                    catch (\Exception $e) {
                        logger('getting delivery fee error : '.$e->getMessage());
                    }

                    $user = $this->get_or_create_rider((object)$data);
                    

                    $ride_request = $this->create_ride_request((object)$data, $user);

                    //create order
                    $new_order = new HomeDeliveryOrder;

                    $accepted_time = new \Carbon\Carbon($order["fulfillment_details"]["accepted_time"]);
                    $fulfill_time = new \Carbon\Carbon($order["fulfillment_details"]["fulfillment_time"]);
                    
                    $est_time = $fulfill_time->diffInMinutes($accepted_time);
                    if($est_time > 60){
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
                    
                    $new_order->distance                = $get_fare_estimation['distance'];
                    $new_order->estimate_time           = $est_time;
                    $new_order->fee                     = 0;
                    $new_order->customer_id             = $user->id;
                    $new_order->ride_request            = $ride_request->id;
                    
                    $new_order->merchant_id             = $merchant_id;

                    $merchant = Merchant::where('id', $merchant_id)->first();
                    try{
                        $new_order->order_description       = $order["fulfillment_details"]["delivery_instructions"];
                    }
                    catch(\Exception $e){
                        $new_order->order_description       = $order["fulfillment_details"]["delivery_instructions"];
                    }
                    $fee = 0.0;
                    // if($data['delivery_fee']){
                    //     $fee = $data['delivery_fee'];
                    // }
                    // else{
                    if(($get_fare_estimation['distance']/1000) > $merchant->delivery_fee_base_distance){
                        $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance']/1000 - $merchant->delivery_fee_base_distance);
                    }
                    else{
                        $fee = $merchant->delivery_fee;
                    }
                    // }
                    $new_order->fee                     = round($fee, 2);
                    
                    $new_order->save();
                    array_push($new_orders,[
                        'order_id' => $new_order->id,
                    ]);

                    logger($new_order->id . ' fulfill time : ' . $order["fulfillment_details"]["fulfillment_time"]);
                    $this->notify_drivers((object)$data, 'New job(s) in your location');                    
                }
                 return response()->json([
                    'status' => 'Successfully created',
                    'orders' => $new_orders,
                ]);
            }
            catch(\Exception $e){
                $data = $request->all();
                //in case of bad order data 
                //put that order information 
                //in db to manual inserting
                $bad_order = new BadOrders;
                $bad_order->secret = $server_key;
                $bad_order->description = json_encode($data);
                $bad_order->save();

                $merchant_name = Merchant::where('shared_secret', $server_key)->first()->name;

                $emails      = ['pardusurbanus@protonmail.com'];
                $content    = [
                    'first_name' => '_'
                ];
                $data['content'] = json_encode($data, JSON_PRETTY_PRINT);
                $data['first_name'] = $content['first_name'];
                $data['merchant'] = $merchant_name;
                // Send Forgot password email to give user email
                foreach($emails as $email){
                    Mail::send('emails.bad_order', $data, function($message) use ($email, $content){
                        $message->to($email, $content['first_name'])->subject('Ride On New bad data order');
                        $message->from('api@rideon.group','Ride on Tech support');
                    });
                }

                return response()->json(['status' => 'Bad request data', 'error' => $e->getMessage()], 400);
            }
        }
        else if($request->isMethod("GET")){
            if($request->has('id')){
                $order = HomeDeliveryOrder::where('id', $request->id)->first();
                if($order){
                    $data = array();
                    $data['order_id'] = $order->id;
                    $data['order_status'] = $order->status;
                    if($order->driver_id && $order->status != 'delivered' && $order->status != 'new'){
                        $driver_loc = DriverLocation::where('user_id',$order->driver_id)->first();
                        $request_loc = RideRequest::where('id', $order->ride_request)->first();
                        try{
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
                                $get_fare_estimation = $this->request_helper->GetDrivingDistance($request_loc->pickup_latitude, $driver_loc->latitude,$request_loc->pickup_longitude, $driver_loc->longitude);
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
                            $time = (int)($get_fare_estimation['time']);
                        }
                        catch(\Exception $e) {
                            $time = null;
                        }
                        $data['ETA'] = $time;
                    }
                    return response()->json(['status' => 'Order found', 'order' => $data]);
                }
                else{
                    return response()->json(['status' => 'Order not found'], 400);
                }
            }
            else{
                return response()->json(['status' => 'Bad request data'], 400);
            }
        }
    }

    /**
     * Webhook for integration with SquareUp 
     * @param Get method request inputs
     *
     * @return Response Json 
     */
    public function square_up(Request $request) 
    {
        $data = $request->all();
        try{
            if($request->isMethod("POST")) {

            // --------------------- Get order info by order_id ----------------
                $merchant = Merchant::where('integration_type', 2)->where('squareup_id', $request->input('merchant_id'))->first();
                if (!$merchant){
                    return response()->json([
                        'status_code'     => '0',
                        'status_message' => 'Validation failed.',
                    ]);
                }

                $order_id = $request->input('data.object.order_created.order_id');
                $location_id = $request->input('data.object.order_created.location_id');

                $access_token = $merchant->shared_secret;

                // curl initiate
                $ch = curl_init();

                // API URL to send data
                $base_url = 'https://connect.squareup.com/v2/locations/';

                if(App::environment(['local','development'])) {
                    $base_url = 'https://connect.squareupsandbox.com/v2/locations/';
                }
                $url = $base_url . $location_id . '/orders/batch-retrieve';
                curl_setopt($ch, CURLOPT_URL, $url);

                // SET Header
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Square-Version: 2020-04-22',
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'));

                // SET Method as a POST
                curl_setopt($ch, CURLOPT_POST, 1);

                // Data should be passed as json format
                $data = array('order_ids'=> array($order_id));
                $data_json = json_encode($data);

                // Pass user data in POST command
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Execute curl and assign returned data
                $response  = curl_exec($ch);
                $tmp = json_decode($response);
                $order = $tmp->orders[0];

                // Close curl
                curl_close($ch);

            // -------------- Get Pickup location by location_id -------------

                // curl initiate
                $ch = curl_init();

                // API URL to send data
                $base_url = 'https://connect.squareup.com/v2/locations/';

                if(App::environment(['local','development'])) {
                    $base_url = 'https://connect.squareupsandbox.com/v2/locations/';
                }
                $url = $base_url . $location_id;
                curl_setopt($ch, CURLOPT_URL, $url);

                // SET Header
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Square-Version: 2020-04-22',
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'));

                // SET Method as a POST
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Execute curl and assign returned data
                $response  = curl_exec($ch);
                $tmp = json_decode($response);

                $pickup_location = $tmp->location->address;
                $data['pick_up_location'] = $pickup_location->address_line_1 . ", " . $pickup_location->locality . ", " . $pickup_location->administrative_district_level_1 . " " . $pickup_location->postal_code . ", " . $pickup_location->country;
                $pickup_geocode = $this->request_helper->GetLatLng($data['pick_up_location']);

                // Close curl
                curl_close($ch);

            // -------------- Get Customer by customer_id -------------

                // // curl initiate
                // $ch = curl_init();

                // // API URL to send data
                // $base_url = 'https://connect.squareup.com/v2/customers/';

                // if(App::environment(['local','development'])) {
                //     $base_url = 'https://connect.squareupsandbox.com/v2/customers/';
                // }
                // $url = $base_url . $order->fulfillments[0]->uid;
                // curl_setopt($ch, CURLOPT_URL, $url);

                // // SET Header
                // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                //     'Square-Version: 2020-04-22',
                //     'Authorization: Bearer ' . $access_token,
                //     'Content-Type: application/json'));

                // // SET Method as a POST
                // curl_setopt($ch, CURLOPT_POST, false);
                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // // Execute curl and assign returned data
                // $response  = curl_exec($ch);
                // $tmp = json_decode($response);
                // if(property_exists($tmp, 'errors')){
                //     throw new \Exception($response);
                //}

                $customer = $order->fulfillments[0]->delivery_details->recipient;
                $data['drop_off_location'] = $customer->address->address_line_1 . ", " . $customer->address->locality . ", " . $customer->address->administrative_district_level_1 . " " . $customer->address->postal_code . ", " . $customer->address->country;
                $dropoff_geocode = $this->request_helper->GetLatLng($data['drop_off_location']);

                // Close curl
                //curl_close($ch);
                
            // ------------------- Create new order ----------------------
                //$merchant_id = Merchant::where('shared_secret', $merchant_key)->first()->id;

                $data['pick_up_latitude'] = $pickup_geocode[0];
                $data['pick_up_longitude'] = $pickup_geocode[1];
                $data['drop_off_longitude'] = $dropoff_geocode[1];
                $data['drop_off_latitude'] = $dropoff_geocode[0];
                $data['country_code'] = "61";
                $data['mobile_number'] = ltrim($customer->phone_number, "+".$data['country_code']);

                $name_arr =  explode(" ", $customer->display_name, 2);

                $data['first_name'] = $name_arr[0];
                $data['last_name'] = $name_arr[1];

                $data['email'] = $customer->email_address;
                
                $data['delivery_fee'] = null;
                if ($order->line_items[0]->name == "DELIVERY_FEE"){
                    $data['delivery_fee'] = (float)100;
                    $data['delivery_fee'] = (float)$order->line_items[0]->total_item_price;
                }
                else{
                    $data['delivery_fee'] = null;
                    $data['delivery_fee_currency'] = "AUD";
                }

                $user = $this->get_or_create_rider((object)$data);

                $ride_request = $this->create_ride_request((object)$data, $user);

                //create order
                $new_order = new HomeDeliveryOrder;
                $timezone = date_default_timezone_get();

                $accepted_time = \Carbon\Carbon::now()->setTimezone($timezone);
                //$fulfill_time = new \Carbon\Carbon($response["fulfill_at"]);
                $fulfill_time = new \Carbon\Carbon($order->fulfillments[0]->delivery_details->deliver_at);

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

                $new_order->distance                = $get_fare_estimation['distance'];
                $new_order->estimate_time           = $fulfill_time->diffInMinutes($accepted_time);
                $new_order->customer_id             = $user->id;
                $new_order->ride_request            = $ride_request->id;
                $new_order->order_description       = $order->fulfillments[0]->delivery_details->note;
                $new_order->merchant_id             = $merchant->id;

                $fee = 0.0;
                // if($data['delivery_fee']){
                //     $fee = $data['delivery_fee'];
                // }
                // else{
                if(($get_fare_estimation['distance']/1000) > $merchant->delivery_fee_base_distance){
                    $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance']/1000 - $merchant->delivery_fee_base_distance);
                }
                else{
                    $fee = $merchant->delivery_fee;
                }
                // }
                $new_order->fee                     = round($fee, 2);
                
                $new_order->save();

                $this->notify_drivers((object)$data, 'New job(s) in your location');
                
                return response()->json([
                    'status_code'     => '1',
                    'status_message' => 'Validation success.',
                ]);
            }
        }
        catch (\Exception $e){
            logger('getting order error : '.$e->getMessage());
            //in case of bad order data 
            //put that order information 
            //in db to manual inserting
            $bad_order = new BadOrders;
            $bad_order->secret = $request->merchant_id;
            $bad_order->description = json_encode($request->all()). $e->getMessage();
            $bad_order->save();
            return response()->json([
                'status_code'     => '0',
                'status_message' => 'Bad order data',
            ]);
        }
    }
    /**
     * Webhook for integration with CloudWaitress 
     * @param Get method request inputs
     *
     * @return Response Json 
     */
    public function cloudwaitress(Request $request) 
    {   
        $server_key = $request->input('secret', null);
        if(!$server_key){
            return response()->json(['status' => 'Unauthorized'], 401);
        }
        
        $merchant = Merchant::where('shared_secret', $server_key)->first(); 
        if(!$merchant) {
			return response()->json(['status' => 'Unauthorized'], 401);
		}

        if($request->isMethod("POST")) {
            $order_status = $request->input('data.order.status');
            $order_service = $request->input('data.order.config.service', 'none');
            if($order_status == "confirmed" && $order_service == "delivery"){
                try{
                    $merchant_id = $merchant->id;

                    $new_orders = array();

                    // -- pick up Location data
                    $data['pick_up_location'] = $request->input('restaurant_address');
                    try{
                        $pickup_geocode = $this->request_helper->GetLatLng($data['pick_up_location']);
                        $data['pick_up_latitude'] = $pickup_geocode[0];
                        $data['pick_up_longitude'] = $pickup_geocode[1];
                    }
                    catch(\Exception $e){
                        logger('getting pick up location error : '.$e->getMessage());
                    }

                    // -- drop off Location data                
                    $data['drop_off_location'] = $request->input('data.customer.delivery.destination');
                    $data['drop_off_latitude'] = $request->input('data.customer.delivery.lat', '0.00');
                    $data['drop_off_longitude'] = $request->input('data.customer.delivery.lng', '0.00');
                    try{
                        if($data['drop_off_latitude'] == '0.00'){
                            $dropoff_geocode = $this->request_helper->GetLatLng($data['drop_off_location']);
                            $data['drop_off_longitude'] = $dropoff_geocode[1];
                            $data['drop_off_latitude'] = $dropoff_geocode[0];
                        }
                    }
                    catch(\Exception $e){
                        logger('getting drop off location error : '.$e->getMessage());
                    }

                    // --- Customer data
                    // --- Cut australian code from client mobile number
                    $full_mobile_number = $request->input('data.customer.details.phone', 'Unknown');

                    $data['mobile_number'] = substr($full_mobile_number, -10);

                    $data['country_code'] = str_replace("+", "", str_replace($data['mobile_number'], "", $full_mobile_number));

                    if($data['country_code'] == ""){
                        $ip_country = $request->input('data.customer.meta.ip_country', 'AU');
                        $data['country_code'] = Country::where('short_name', $ip_country)->first()->phone_code;
                    }

                    $full_name = $request->input('data.customer.details.name');
                    $name_arr =  explode(" ", $full_name, 2);

                    $data['first_name'] = $name_arr[0];
                    $data['last_name'] = $name_arr[0];
                    if(count($name_arr) > 1){
                        $data['last_name'] = $name_arr[1];
                    }
                    
                    $data['email'] = $request->input('data.customer.details.email', $data['mobile_number']."@none.exist");
                    
                    $data['delivery_fee'] = null;
                    $data['delivery_fee_currency'] = "AUD";
                    
                    $user = $this->get_or_create_rider((object)$data);
                    
                    $ride_request = $this->create_ride_request((object)$data, $user);

                    //create order
                    $new_order = new HomeDeliveryOrder;

                    $accepted_time = \Carbon\Carbon::now();

                    $time_to = $request->input('data.order.delivery_in.timestamp');

                    if($time_to){
                        $fulfill_time =  \Carbon\Carbon::createFromTimestampMs($request->input('data.order.delivery_in.timestamp'));
                        $est_time = $fulfill_time->diffInMinutes($accepted_time);
                    }
                    else{
                        $est_time = 5;
                    }

                    if($est_time > 60){
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
                    
                    $new_order->distance                = $get_fare_estimation['distance'];
                    $new_order->estimate_time           = $est_time;
                    $new_order->fee                     = 0;
                    $new_order->customer_id             = $user->id;
                    $new_order->ride_request            = $ride_request->id;
                    
                    $new_order->merchant_id             = $merchant_id;

                    try{
                        $new_order->order_description       = $request->input('data.order.notes');
                    }
                    catch(\Exception $e){
                        $new_order->order_description       = $request->input('data.order.notes');
                    }
                    $fee = 0.0;
                    // if($data['delivery_fee']){
                    //     $fee = $data['delivery_fee'];
                    // }
                    // else{
                    if(($get_fare_estimation['distance']/1000) > $merchant->delivery_fee_base_distance){
                        $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance']/1000 - $merchant->delivery_fee_base_distance);
                    }
                    else{
                        $fee = $merchant->delivery_fee;
                    }
                    // }
                    $new_order->fee                     = round($fee, 2);
                    
                    $new_order->save();
                    array_push($new_orders,[
                        'order_id' => $new_order->id,
                    ]);

                    logger($new_order->id . ' fulfill time : ' . $fulfill_time);
                    $this->notify_drivers((object)$data, 'New job(s) in your location');                    
                    
                    return response()->json([
                        'status' => 'Successfully created',
                        'orders' => $new_orders,
                    ]);
                }
                catch(\Exception $e){
                    $data = $request->all();
                    //in case of bad order data 
                    //put that order information 
                    //in db to manual inserting
                    $bad_order = new BadOrders;
                    $bad_order->secret = $server_key;
                    $bad_order->description = json_encode($data);
                    $bad_order->save();

                    $merchant_name = Merchant::where('shared_secret', $server_key)->first()->name;

                    $emails      = ['pardusurbanus@protonmail.com'];
                    $content    = [
                        'first_name' => '_'
                    ];
                    $data['content'] = json_encode($data, JSON_PRETTY_PRINT);
                    $data['first_name'] = $content['first_name'];
                    $data['merchant'] = $merchant_name;
                    // Send Forgot password email to give user email
                    foreach($emails as $email){
                        Mail::send('emails.bad_order', $data, function($message) use ($email, $content){
                            $message->to($email, $content['first_name'])->subject('Ride On New bad data order');
                            $message->from('api@rideon.group','Ride on Tech support');
                        });
                    }

                    return response()->json(['status' => 'Bad request data', 'error' => $e->getMessage()], 400);
                }
            }
        }
    }

    /**
     * Webhook for integration with Shopify 
     * @param Get method request inputs
     *
     * @return Response Json 
     */
    public function shopify(Request $request) 
    {
        if($request->isMethod("POST")) {

            $shopify_merchants = Merchant::where('integration_type', 3)->get();

            $merchant_id = 0;
            $app_secret = '';
            $hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
            $body = file_get_contents('php://input');

            foreach ($shopify_merchants as $merchant)
            {
                $calculated_hmac = base64_encode(hash_hmac('sha256', $body, $merchant->shared_secret, true));
                
                if (hash_equals($hmac_header, $calculated_hmac))
                {
                    $merchant_id = $merchant->id;
                    $app_secret = $merchant->shared_secret;
                    break;
                }
            }

            if ($merchant_id == 0)
            {
                return response()->json([
                    'status_code'     => '1',
                    'status_message' => 'Validation failed',
                ]);
            }

            $data['pick_up_latitude'] = $request->billing_address['latitude'] ? $request->billing_address['latitude'] : 37.00;
            $data['pick_up_longitude'] = $request->billing_address['longitude'] ? $request->billing_address['longitude'] : -87.00;
            $data['pick_up_location'] = $request->billing_address['address1'] . ' ' . $request->billing_address['city'];
            $data['drop_off_longitude'] = $request->shipping_address['longitude'] ? $request->shipping_address['longitude'] : -87.20;
            $data['drop_off_latitude'] = $request->shipping_address['latitude'] ? $request->shipping_address['latitude'] : 37.20;
            $data['drop_off_location'] = $request->shipping_address['address1'] . ' ' . $request->shipping_address['city'];
            $data['country_code'] = "61";
            $data['mobile_number'] = ltrim($request->customer['phone'], "+".$data['country_code']);

            $data['first_name'] = $request->customer['first_name'];
            $data['last_name'] = $request->customer['last_name'];
            $data['email'] = $request->customer['email'];            
            $data['delivery_fee'] = (float)$request->total_price;

            $user = $this->get_or_create_rider((object)$data);

            $ride_request = $this->create_ride_request((object)$data, $user);

            //create order
            $new_order = new HomeDeliveryOrder;

            //$accepted_time = new \Carbon\Carbon($order["accepted_at"]);
            //$fulfill_time = new \Carbon\Carbon($order["fulfill_at"]);

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

            $new_order->distance                = $get_fare_estimation['distance'];
            //$new_order->estimate_time           = $fulfill_time->diffInMinutes($accepted_time);
            $new_order->fee                     = 0;
            $new_order->customer_id             = $user->id;
            $new_order->ride_request            = $ride_request->id;
            //$new_order->order_description       = $order["instructions"];
            $new_order->merchant_id             = $merchant_id;

            $merchant = Merchant::where('id', $merchant_id)->first();
            // $fee = 0.0;
            // if($data['delivery_fee']){
            //     $fee = $data['delivery_fee'];
            // }
            // else{
            if(($get_fare_estimation['distance']/1000) > $merchant->delivery_fee_base_distance){
                $fee = $merchant->delivery_fee + $merchant->delivery_fee_per_km * ($get_fare_estimation['distance']/1000 - $merchant->delivery_fee_base_distance);
            }
            else{
                $fee = $merchant->delivery_fee;
            }
            // }
            $new_order->fee                     = round($fee, 2);
            
            $new_order->save();

            $this->notify_drivers((object)$data, 'New job(s) in your location');
            
            return response()->json([
                'status_code'     => '1',
                'status_message' => 'Successfully created',
            ]);
        }
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
            $user->password         =   Str::random();
            $user->country_code     =   $request->country_code;
            $user->language         =   $language;
            $user->email            =   $request->email;
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
    public function notify_drivers($request, $message)
    {  
        $nearest_cars = DriverLocation::select(DB::raw('*, ( 6371 * acos( cos( radians(' . $request->pick_up_latitude . ') ) * cos( radians( latitude ) ) * cos(radians( longitude ) - radians(' . $request->pick_up_longitude . ') ) + sin( radians(' . $request->pick_up_latitude . ') ) * sin( radians( latitude ) ) ) ) as distance'))
            ->having('distance', '<=', 15)->get();

            foreach ($nearest_cars as $nearest_car) {
                $driver_details = User::where('id', $nearest_car->user_id)->first();

                if($driver_details->device_id != "" && $driver_details->status == "Active")
                {    
                    $this->send_custom_pushnotification($driver_details->device_id,$driver_details->device_type,$driver_details->user_type,$message);    
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
        try{
            $polyline = $this->request_helper->GetPolyline($request->pick_up_latitude, $request->drop_off_latitude, $request->pick_up_longitude, $request->drop_off_longitude);
        }
        catch (\Exception $e) {
            logger('polyline getting exception : '.$e->getMessage());
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