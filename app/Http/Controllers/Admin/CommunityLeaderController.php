<?php

/**
 * Driver Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Driver
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\DataTables\CommunityLeadersDataTable;
use App\Models\User;
use App\Models\Trips;
use App\Models\DriverAddress;
use App\Models\DriverDocuments;
use App\Models\DriversSubscriptions;
use App\Models\StripeSubscriptionsPlans;
use App\Models\Country;
use App\Models\CarType;
use App\Models\ProfilePicture;
use App\Models\Company;
use App\Models\Vehicle;
use App\Models\ReferralUser;
use App\Models\ReferralSetting;
use App\Models\DriverOweAmount;
use App\Models\PayoutPreference;
use App\Models\PayoutCredentials;
use Validator;
use DB;
use Image;
use Auth;
use App;

use Illuminate\Support\Facades\Hash;


use App\Http\Start\Helpers;
use App\Models\PasswordResets;
use App\Mail\ForgotPasswordMail;
use Mail;
use URL;

class CommunityLeaderController extends Controller
{

    protected $helper;  // Global variable for instance of Helpers

    public function __construct()
    {
        $this->helper = new Helpers;
        $this->otp_helper = resolve('App\Http\Helper\OtpHelper');
    }


    /**
     * Load Datatable for CommunityLeader
     *
     * @param array $dataTable  Instance of CommunityLeader DataTable
     * @return datatable
     */
    public function index(CommunityLeadersDataTable $dataTable)
    {
        return $dataTable->render('admin.community_leader.view');
    }

    /**
     * Add a New CommunityLeader
     *
     * @param array $request  Input values
     * @return redirect     to CommunityLeader view
     */
    public function add(Request $request)
    {
        if($request->isMethod("GET")) {
            //Inactive Company could not add CommunityLeader
            if (LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }
            $data['country_code_option']=Country::select('long_name','phone_code')->get();
            $data['country_name_option']=Country::pluck('long_name', 'short_name');
            $data['company']=Company::where('status','Active')->pluck('name','id');
            return view('admin.community_leader.add',$data);
        }

        if($request->submit) {
            // Add CommunityLeader Validation Rules
            $rules = array(
                'first_name'    => 'required',
                'last_name'     => 'required',
                'email'         => 'required|email',
                'mobile_number' => 'required|regex:/[0-9]{6}/',
                'password'      => 'required',
                'country_code'  => 'required',
                'user_type'     => 'required',

                'status'        => 'required',
                'license_front' => 'required|mimes:jpg,jpeg,png,gif',
                'license_back'  => 'required|mimes:jpg,jpeg,png,gif',
            );

            //Bank details are required only for company drivers & Not required for Admin drivers
            if ((LOGIN_USER_TYPE!='company' && $request->company_name != 1) || (LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->id!=1)) {
                $rules['account_holder_name'] = 'required';
                $rules['account_number'] = 'required';
                $rules['bank_name'] = 'required';
                $rules['bank_location'] = 'required';
                $rules['bank_code'] = 'required';
            }

            if (LOGIN_USER_TYPE!='company') {
                $rules['company_name'] = 'required';
            }

            // Add Driver Validation Custom Names
            $attributes = array(
                'first_name'    => trans('messages.user.firstname'),
                'last_name'     => trans('messages.user.lastname'),
                'email'         => trans('messages.user.email'),
                'password'      => trans('messages.user.paswrd'),
                'country_code'  => trans('messages.user.country_code'),
                'user_type'     => trans('messages.user.user_type'),
                'status'        => trans('messages.driver_dashboard.status'),
                'license_front' => trans('messages.driver_dashboard.driver_license_front'),
                'license_back'  => trans('messages.driver_dashboard.driver_license_back'),
                'account_holder_name'  => 'Account Holder Name',
                'account_number'  => 'Account Number',
                'bank_name'  => 'Name of Bank',
                'bank_location'  => 'Bank Location',
                'bank_code'  => 'BIC/SWIFT Code',
            );
                // Edit Rider Validation Custom Fields message
            $messages =array(
                'required'            => ':attribute is required.',
                'mobile_number.regex' => trans('messages.user.mobile_no'),
            );
            $validator = Validator::make($request->all(), $rules,$messages, $attributes);

            $validator->after(function ($validator) use($request) {
                $user = User::where('mobile_number', $request->mobile_number)->where('user_type', $request->user_type)->count();

                $user_email = User::where('email', $request->email)->where('user_type', $request->user_type)->count();

                if($user) {
                   $validator->errors()->add('mobile_number',trans('messages.user.mobile_no_exists'));
                }

                if($user_email) {
                   $validator->errors()->add('email',trans('messages.user.email_exists'));
                }
            });

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $user = new User;

            $user->first_name   = $request->first_name;
            $user->last_name    = $request->last_name;
            $user->email        = $request->email;
            $user->country_code = $request->country_code;
            $user->mobile_number= $request->mobile_number;
            $user->password     = $request->password;
            $user->status       = $request->status;
            $user->user_type    = $request->user_type;
            $user->status       = $request->status;

            if (LOGIN_USER_TYPE=='company') {
                $user->company_id       = Auth::guard('company')->user()->id;
            }
            else {
                $user->company_id       = $request->company_name;
            }
            if($user->save()) {
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

            $user_pic = new ProfilePicture;
            $user_pic->user_id      =   $user->id;
            $user_pic->src          =   "";
            $user_pic->photo_source =   'Local';
            $user_pic->save();

            $user_address = new DriverAddress;
            $user_address->user_id       =   $user->id;
            $user_address->address_line1 =   $request->address_line1 ? $request->address_line1 :'';
            $user_address->address_line2 =   $request->address_line2 ? $request->address_line2:'';
            $user_address->city          =   $request->city ? $request->city:'';
            $user_address->state         =   $request->state ? $request->state:'';
            $user_address->postal_code   =   $request->postal_code ? $request->postal_code:'';
            $user_address->save();

            if ($user->company_id != null && $user->company_id != 1) {
                $payout_preference = PayoutPreference::firstOrNew(['user_id' => $user->id,'payout_method' => "BankTransfer"]);
                $payout_preference->user_id = $user->id;
                $payout_preference->country = "IN";
                $payout_preference->account_number  = $request->account_number;
                $payout_preference->holder_name     = $request->account_holder_name;
                $payout_preference->holder_type     = "company";
                $payout_preference->paypal_email    = $request->account_number;

                $payout_preference->phone_number    = $request->mobile_number ?? '';
                $payout_preference->branch_code     = $request->bank_code ?? '';
                $payout_preference->bank_name       = $request->bank_name ?? '';
                $payout_preference->bank_location   = $request->bank_location ?? '';
                $payout_preference->payout_method   = "BankTransfer";
                $payout_preference->address_kanji   = json_encode([]);
                $payout_preference->save();

                $payout_credentials = PayoutCredentials::firstOrNew(['user_id' => $user->id,'type' => "BankTransfer"]);
                $payout_credentials->user_id = $user->id;
                $payout_credentials->preference_id = $payout_preference->id;
                $payout_credentials->payout_id = $request->account_number;
                $payout_credentials->type = "BankTransfer";
                $payout_credentials->default = 'yes';

                $payout_credentials->save();
            }

            $user_doc = new DriverDocuments;
            $user_doc->user_id = $user->id;

            $image_uploader = resolve('App\Contracts\ImageHandlerInterface');
            $target_dir = '/images/users/'.$user->id;
            $target_path = asset($target_dir).'/';

            if($request->hasFile('license_front')) {
                $license_front = $request->file('license_front');

                $extension = $license_front->getClientOriginalExtension();
                $file_name = "license_front_".time().".".$extension;
                $options = compact('target_dir','file_name');

                $upload_result = $image_uploader->upload($license_front,$options);
                if(!$upload_result['status']) {
                    flashMessage('danger', $upload_result['status_message']);
                    return back();
                }

                $user_doc->license_front = $target_path.$upload_result['file_name'];
            }
            if($request->hasFile('license_back')) {
                $license_back = $request->file('license_back');

                $extension = $license_back->getClientOriginalExtension();
                $file_name = "license_back_".time().".".$extension;
                $options = compact('target_dir','file_name');

                $upload_result = $image_uploader->upload($license_back,$options);
                if(!$upload_result['status']) {
                    flashMessage('danger', $upload_result['status_message']);
                    return back();
                }

                $user_doc->license_back = $target_path.$upload_result['file_name'];
            }

            $user_doc->save();


            $plan = StripeSubscriptionsPlans::where('plan_name','Regular')->first();
            $subscription_row = new DriversSubscriptions;
            $subscription_row->user_id      = $user->id;
            $subscription_row->stripe_id    = '';
            $subscription_row->status       = 'subscribed';
            $subscription_row->email        = $user->email;
            $subscription_row->plan         = $plan->id;
            $subscription_row->country      = '';
            $subscription_row->card_name    = '';
            $subscription_row->save();

            flashMessage('success', trans('messages.user.add_success'));

            return redirect(LOGIN_USER_TYPE.'/community_leader');
        }

        return redirect(LOGIN_USER_TYPE.'/community_leader');
    }

        /**
     * Add payout Preferences
     *
     * @param  Post method inputs
     * @return Response in Json
     */
    private function updatePayoutPreference($payout_data)
    {
        $user_details = User::find($payout_data->user_id);
        $payout_methods = getPayoutMethods($user_details->company_id);
        $payout_methods = implode(',',$payout_methods);
        $payout_data->user = $user_details;

        try{
            $user_id = $user_details->id;
            $country = $payout_data->country;

            $payout_default_count = PayoutCredentials::where('user_id', $user_id)->where('default', '=', 'yes');
            $account_holder_type = 'company';
            $payout_method = snakeToCamel($payout_data->payout_method,true);
            $payout_service = resolve('App\Services\Payouts\\'.$payout_method.'Payout');

            if ($payout_method == 'Stripe') {
                $account_holder_type = 'individual';

                $payout_data->payout_country = $country;
                $iban_supported_country = Country::getIbanRequiredCountries();

                $bank_data = array(
                    "country"               => $country,
                    "currency"              => $payout_data->currency,
                    "account_holder_name"   => $payout_data->account_holder_name,
                    "account_holder_type"   => $account_holder_type,
                );

                if (in_array($country, $iban_supported_country)) {
                    $payout_data->account_number = $payout_data->iban;
                    $bank_data['account_number'] = $payout_data->iban;
                }
                else {
                    if ($country == 'AU') {
                        $payout_data->routing_number = $payout_data->bsb;
                    }
                    elseif ($country == 'HK') {
                        $payout_data['routing_number'] = $payout_data->clearing_code . '-' . $payout_data->branch_code;
                    }
                    elseif ($country == 'JP' || $country == 'SG') {
                        $payout_data['routing_number'] = $payout_data->bank_code . $payout_data->branch_code;
                    }
                    elseif ($country == 'GB') {
                        $payout_data['routing_number'] = $payout_data->sort_code;
                    }
                    $bank_data['routing_number'] = $payout_data->routing_number;
                    $bank_data['account_number'] = $payout_data->account_number;
                }
            }

            // $validate_data = $payout_service->validateRequest($payout_data);

            // if($validate_data) {
            //     return $validate_data;
            // }

            $document_path = $docfile = $payout_data->id_file_path;
            $file_name = $payout_data->id_file_name;

            if ($payout_method == 'Stripe') {
                $stripe_token = $payout_service->createStripeToken($bank_data);

                if(!$stripe_token['status']) {
                    return (object)[
                        'status_code' => '0',
                        'status_message' => $stripe_token['status_message'],
                    ];
                }

                $payout_data->stripe_token = $stripe_token['token'];

                $payout_preference = PayoutPreference::where('user_id',$user_id)->first();

                if($payout_preference){
                    $stripe_preference = $payout_service->updatePayoutPreference($payout_preference->paypal_email, $payout_data);
                }
                else{
                    $stripe_preference = $payout_service->createPayoutPreference($payout_data);
                }

                if(!$stripe_preference['status']) {
                    return (object)[
                        'status_code' => '0',
                        'status_message' => $stripe_preference['status_message'],
                    ];
                }

                $recipient = $stripe_preference['recipient'];
                if(isset($document_path)) {

                    $document_result = $payout_service->uploadDocument($docfile,$recipient->id);
                    if(!$document_result['status']) {
                        return (object)[
                            'status_code' => '0',
                            'status_message' => $document_result['status_message'],
                        ];
                    }

                    $stripe_document = $document_result['stripe_document'];
                }

                $payout_email = isset($recipient->id) ? $recipient->id : $user_details->email;
                $payout_currency = $payout_data->currency ?? '';
            }

            if ($payout_method == 'Paypal') {
                $payout_email = $payout_data->email;
                $payout_currency = PAYPAL_CURRENCY_CODE;
            }

            if ($payout_method == 'BankTransfer') {
                $payout_email       = $payout_data->account_number;
                $payout_currency    = "";
                $payout_data['branch_code']= $payout_data->bank_code;
            }

            $payout_preference = PayoutPreference::firstOrNew(['user_id' => $user_id,'payout_method' => $payout_method]);

            $payout_preference->user_id         = $user_id;
            $payout_preference->country         = $country;
            $payout_preference->currency_code   = $payout_currency;
            $payout_preference->routing_number  = $payout_data->routing_number ?? '';
            $payout_preference->account_number  = $payout_data->account_number ?? '';
            $payout_preference->holder_name     = $payout_data->account_holder_name ?? '';
            $payout_preference->holder_type     = $account_holder_type;
            $payout_preference->paypal_email    = $payout_email;
            $payout_preference->address1    = $payout_data->address1 ?? '';
            $payout_preference->address2    = $payout_data->address2 ?? '';
            $payout_preference->city        = $payout_data->city;
            $payout_preference->state       = $payout_data->state;
            $payout_preference->postal_code = $payout_data->postal_code;
            if (isset($document_path)) {
                $payout_preference->document_id     = $stripe_document ?? '';
                $payout_preference->document_image  = $file_name;
            }
            $payout_preference->phone_number    = $payout_data->phone_number ?? '';
            $payout_preference->branch_code     = $payout_data->branch_code ?? '';
            $payout_preference->bank_name       = $payout_data->bank_name ?? '';
            $payout_preference->branch_name     = $payout_data->branch_name ?? '';
            $payout_preference->bank_location     = $payout_data->bank_location ?? '';
            $payout_preference->ssn_last_4      = $country == 'US' ? $payout_data->ssn_last_4 : '';
            $payout_preference->payout_method   = $payout_method;
            $payout_preference->address_kanji   = isset($address_kanji) ? json_encode($address_kanji) : json_encode([]);
            $payout_preference->save();

            $payout_credentials = PayoutCredentials::firstOrNew(['user_id' => $user_id,'type' => $payout_method]);

            $payout_credentials->user_id = $user_id;
            $payout_credentials->preference_id = $payout_preference->id;
            $payout_credentials->payout_id = $payout_email;
            $payout_credentials->type = $payout_method;

            if($payout_credentials->default != 'yes') {
                $payout_credentials->default = $payout_default_count->count() == 0 ? 'yes' : 'no';
            }

            $payout_credentials->save();

            return (object)[
                'status_code' => '1',
                'status_message' => 'Uploaded',
            ];
        }
        catch(\Exception $e){
            logger($e->getMessage());
            return (object)[
                'status_code' => '0',
                'status_message' => ''.$e->getMessage(),
            ];
        }
    }

    /**
     * Update CommunityLeader Details
     *
     * @param array $request    Input values
     * @return redirect     to CommunityLeader View
     */
    public function update(Request $request)
    {
        if($request->isMethod("GET")) {
            $data['result']             = User::find($request->id);
            $data['profile_image'] = ProfilePicture::where('user_id',$request->id)->first();

            //If login user is company then company can edit only that company's driver details
            if($data['result'] && (LOGIN_USER_TYPE!='company' || Auth::guard('company')->user()->id == $data['result']->company_id)) {
                $data['address']            = DriverAddress::where('user_id',$request->id)->first();
                $data['driver_documents']   = DriverDocuments::where('user_id',$request->id)->first();
                $data['country_code_option']=Country::select('long_name','phone_code')->get();
                $data['company']=Company::where('status','Active')->pluck('name','id');
                $data['payout_preference'] = PayoutPreference::where('user_id', $request->id)->first();
                $data['payout_credentials'] = PayoutCredentials::where('user_id',$request->id)->first();
                if ($data['payout_preference']){
                    if(App::environment(['production'])) {
                        $target_dir = 'images/users/'.$request->id;
                        $pic_path = Storage::disk('do_spaces')->url($target_dir.'/'.$data['payout_preference']->document_image);
                    }
                    else{
                        $pic_path = url('images/users/'.$request->id) . '/' . $data['payout_preference']->document_image;
                    }
                    $data['payout_preference']->document_image = $pic_path;
                }
                else{
                    $data['payout_preference'] = (object) array('document_image' => null, "routing_number" => null, "account_number"=> null, "holder_name" => null,);
                }
                $data['path']               = url('images/users/'.$request->id);
                $data ['subscription'] = DriversSubscriptions::where('user_id', $request->id)->first();
                $data['current_plan'] = StripeSubscriptionsPlans::find($data['subscription']->plan);
                $data['all_plans'] = StripeSubscriptionsPlans::get();

                $usedRef = User::where('referral_code', $data['result']->used_referral_code)->first();
                if($usedRef){
                    $data['referrer'] = $usedRef->id;
                }
                else{
                    $data['referrer'] = null;
                }


                return view('admin.community_leader.edit', $data);
            }

            flashMessage('danger', 'Invalid ID');
            return redirect(LOGIN_USER_TYPE.'/community_leader');
        }



        if($request->submit) {
            // Edit Driver Validation Rules
            $rules = array(
                'first_name'    => 'required',
                'last_name'     => 'required',
                'email'         => 'required|email',
                'status'        => 'required',
                // 'mobile_number' => 'required|regex:/[0-9]{6}/',
                'referral_code' => 'required',
                //'used_referral_code' => 'nullable',
                'plan_id'       => 'required',
                'country_code'  => 'required',
                'license_front' => 'mimes:jpg,jpeg,png,gif',
                'license_back'  => 'mimes:jpg,jpeg,png,gif',
            );

            //Bank details are updated only for company's drivers.
            if ((LOGIN_USER_TYPE!='company' && $request->company_name != 1) || (LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->id!=1)) {
                $rules['account_holder_name'] = 'required';
                $rules['account_number'] = 'required';
                $rules['bank_name'] = 'required';
                $rules['bank_location'] = 'required';
                $rules['bank_code'] = 'required';
            }

            if (LOGIN_USER_TYPE!='company') {
                $rules['company_name'] = 'required';
            }


            // Edit Driver Validation Custom Fields Name
            $attributes = array(
                'first_name'    => trans('messages.user.firstname'),
                'last_name'     => trans('messages.user.lastname'),
                'email'         => trans('messages.user.email'),
                'status'        => trans('messages.driver_dashboard.status'),
                'mobile_number' => trans('messages.profile.phone'),
                'country_ode'   => trans('messages.user.country_code'),
                'license_front' => trans('messages.signup.license_front'),
                'license_back'  => trans('messages.signup.license_back'),
                'license_front' => trans('messages.user.driver_license_front'),
                'license_back'  => trans('messages.user.driver_license_back'),
                'account_holder_name'  => 'Account Holder Name',
                'account_number'  => 'Account Number',
                'bank_name'  => 'Name of Bank',
                'bank_location'  => 'Bank Location',
                'bank_code'  => 'BIC/SWIFT Code',
            );

            // Edit Rider Validation Custom Fields message
            $messages = array(
                'required'            => ':attribute is required.',
                'mobile_number.regex' => trans('messages.user.mobile_no'),
            );

            $validator = Validator::make($request->all(), $rules,$messages, $attributes);
            if($request->mobile_number!="") {
                $validator->after(function ($validator) use($request) {
                    $user = User::where('mobile_number', $request->mobile_number)->where('user_type', $request->user_type)->where('id','!=', $request->id)->count();

                    if($user) {
                       $validator->errors()->add('mobile_number',trans('messages.user.mobile_no_exists'));
                    }
                });
            }

            $validator->after(function ($validator) use($request) {
                $user_email = User::where('email', $request->email)->where('user_type', $request->user_type)->where('id','!=', $request->id)->count();

                if($user_email) {
                    $validator->errors()->add('email',trans('messages.user.email_exists'));
                }

                //--- Konstantin N edits: refferal checking for coincidence
                $referral_c = User::where('referral_code', $request->referral_code)->where('user_type', $request->user_type)->where('id','!=', $request->id)->count();

                if($referral_c){
                    $validator->errors()->add('referral_code',trans('messages.referrals.referral_exists'));
                }

            });

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput(); // Form calling with Errors and Input values
            }

            $country_code = $request->country_code;

            $user = User::find($request->id);

            $user->first_name   = $request->first_name;
            $user->last_name    = $request->last_name;
            $user->email        = $request->email;
            $user->status       = $request->status;
            $user->country_code = $country_code;
            $user->referral_code = $request->referral_code;

            //$usedRef = ReferralUser::where([['user_id', "=",  $request->id],['payment_status', '=', 'Expired']])->first();

            //find user by refferer_id
            $usedRef = User::find($request->referrer_id);
            if($usedRef){
                //remove old reference if used referral code updated
                if($usedRef->used_referral_code != $user->used_referral_code){
                    $old_reffered = User::where('referral_code', $user->used_referral_code)->first();
                    if($old_reffered){
                        $reference = ReferralUser::where('user_id', $old_reffered->id)->where('referral_id', $request->id)->first();
                        if($reference){
                            $reference->delete();
                        }
                    }
                }

                //get reffernce between referred user and current user
                $reference = ReferralUser::where('user_id', $usedRef->id)->where('referral_id', $request->id)->first();

                if(!$reference) {
                    //if there is no reference between users, create it
                    $referrel_user = new ReferralUser;
                    $referrel_user->referral_id = $user->id;
                    $referrel_user->user_id     = $usedRef->id;
                    $referrel_user->user_type   = $usedRef->user_type;
                    $referrel_user->save();
                }

                $user->used_referral_code = $usedRef->referral_code;

            }

            if($request->mobile_number!="") {
                $user->mobile_number = $request->mobile_number;
            }
            $user->user_type    = $request->user_type;

            if($request->password != '') {
                $user->password = $request->password;
            }

            if (LOGIN_USER_TYPE=='company') {
                $user->company_id       = Auth::guard('company')->user()->id;
            }
            else {
                $user->company_id       = $request->company_name;
            }

            Vehicle::where('user_id',$user->id)->update(['company_id'=>$user->company_id]);

            $user->save();

            $subscription = DriversSubscriptions::where('user_id', $user->id)->first();
            $subscription->plan = $request->plan_id;
            $subscription->save();

            $user_address = DriverAddress::where('user_id',  $user->id)->first();
            if($user_address == '') {
                $user_address = new DriverAddress;
            }

            $user_address->user_id       = $user->id;
            $user_address->address_line1 = $request->address_line1;
            $user_address->address_line2 = $request->address_line2;
            $user_address->city          = $request->city;
            $user_address->state         = $request->state;
            $user_address->postal_code   = $request->postal_code;
            $user_address->save();

            if ($user->company_id != null && $user->company_id != 1) {
                $payout_preference = PayoutPreference::firstOrNew(['user_id' => $user->id,'payout_method' => "BankTransfer"]);
                $payout_preference->user_id = $user->id;
                $payout_preference->country = "IN";
                $payout_preference->account_number  = $request->account_number;
                $payout_preference->holder_name     = $request->account_holder_name;
                $payout_preference->holder_type     = "company";
                $payout_preference->paypal_email    = $request->account_number;

                $payout_preference->phone_number    = $request->mobile_number ?? '';
                $payout_preference->branch_code     = $request->bank_code ?? '';
                $payout_preference->bank_name       = $request->bank_name ?? '';
                $payout_preference->bank_location   = $request->bank_location ?? '';
                $payout_preference->payout_method   = "BankTransfer";
                $payout_preference->address_kanji   = json_encode([]);
                $payout_preference->save();

                $payout_credentials = PayoutCredentials::firstOrNew(['user_id' => $user->id,'type' => "BankTransfer"]);
                $payout_credentials->user_id = $user->id;
                $payout_credentials->preference_id = $payout_preference->id;
                $payout_credentials->payout_id = $request->account_number;
                $payout_credentials->type = "BankTransfer";
                $payout_credentials->default = 'yes';
                $payout_credentials->save();
            }

                        $user_doc = DriverDocuments::where('user_id',  $user->id)->firstOrNew(['user_id' => $user->id]);

            $user_picture = ProfilePicture::where('user_id',$request->id)->first();

            $image_uploader = resolve('App\Contracts\ImageHandlerInterface');
            if(App::environment(['production'])) {
                $target_dir = 'images/users/'.$user->id;
            }
            else{
                $target_dir = '/images/users/'.$user->id;
            }
            $target_path = asset($target_dir).'/';

            if(App::environment(['production'])) {
                if($request->hasFile('license_front')) {
                    $license_front = $request->file('license_front');

                    $extension = $license_front->getClientOriginalExtension();
                    $file_name = "license_front_" . $user->id . "." . $extension;

                    $upload_result = Storage::disk('do_spaces')->putFileAs($target_dir, $license_front, $file_name, 'public');

                    if(!$upload_result) {
                        flashMessage('danger', 'can\'t upload license_front image');
                        return back();
                    }

                    $user_doc->license_front = Storage::disk('do_spaces')->url($target_dir.'/'.$file_name);
                }
                if($request->hasFile('license_back')) {
                    $license_back = $request->file('license_back');

                    $extension = $license_back->getClientOriginalExtension();
                    $file_name = "license_back_" . $user->id . "." . $extension;

                    $upload_result = Storage::disk('do_spaces')->putFileAs($target_dir, $license_back, $file_name, 'public');

                    if(!$upload_result) {
                        flashMessage('danger', 'can\'t upload license_back image');
                        return back();
                    }
                    $user_doc->license_back = Storage::disk('do_spaces')->url($target_dir.'/'.$file_name);
                }
                if($request->hasFile('profile_image')) {
                    $profile_image = $request->file('profile_image');

                    $extension = $profile_image->getClientOriginalExtension();
                    $file_name = "profile_picture_" . $user->id . "." . $extension;

                    $upload_result = Storage::disk('do_spaces')->putFileAs($target_dir, $profile_image, $file_name, 'public');

                    if(!$upload_result) {
                        flashMessage('danger', 'can\'t upload profile image');
                        return back();
                    }
                    $user_picture->src = Storage::disk('do_spaces')->url($target_dir.'/'.$file_name);
                }
                if($request->hasFile('abn_number')) {
                    $abn_number = $request->file('abn_number');

                    $extension = $abn_number->getClientOriginalExtension();
                    $file_name = "abn_number_" . $user->id . "." . $extension;

                    $upload_result = Storage::disk('do_spaces')->putFileAs($target_dir, $abn_number, $file_name, 'public');

                    if(!$upload_result) {
                        flashMessage('danger', 'can\'t upload abn_number image');
                        return back();
                    }
                    $user_doc->abn_number = Storage::disk('do_spaces')->url($target_dir.'/'.$file_name);
                }
                if($request->hasFile('right_to_work')) {
                    $right_to_work = $request->file('right_to_work');

                    $extension = $right_to_work->getClientOriginalExtension();
                    $file_name = "right_to_work_" . $user->id . "." . $extension;

                    $upload_result = Storage::disk('do_spaces')->putFileAs($target_dir, $right_to_work, $file_name, 'public');

                    if(!$upload_result) {
                        flashMessage('danger', 'can\'t upload right_to_work image');
                        return back();
                    }
                    $user_doc->right_to_work = Storage::disk('do_spaces')->url($target_dir.'/'.$file_name);
                }
                if($request->hasFile('id_document')) {
                    $id_document = $request->file('id_document');

                    $extension = $id_document->getClientOriginalExtension();
                    $file_name = "id_document_" . $user->id . "." . $extension;

                    $document_id_file_name  = $file_name;
                    $upload_result = Storage::disk('do_spaces')->putFileAs($target_dir, $id_document, $file_name, 'public');

                    if(!$upload_result) {
                        flashMessage('danger', 'can\'t upload id_document image');
                        return back();
                    }
                    //$document_id_file_path = Storage::disk('do_spaces')->url($target_dir.'/'.$file_name);
                    $document_id_file_path = Storage::disk('local')->putFileAs('doc', $id_document, $file_name);
                    $document_id_file_path = Storage::disk('local')->getAdapter()->applyPathPrefix($document_id_file_path);
                }
            }
            else{
                if($request->hasFile('license_front')) {
                    $license_front = $request->file('license_front');

                    $extension = $license_front->getClientOriginalExtension();
                    $file_name = "license_front_" . $user->id . "." . $extension;
                    $options = compact('target_dir','file_name');

                    $upload_result = $image_uploader->upload($license_front,$options);
                    if(!$upload_result['status']) {
                        flashMessage('danger', $upload_result['status_message']);
                        return back();
                    }

                    $user_doc->license_front = $target_path.$upload_result['file_name'];
                }
                if($request->hasFile('license_back')) {
                    $license_back = $request->file('license_back');

                    $extension = $license_back->getClientOriginalExtension();
                    $file_name = "license_back_" . $user->id . "." . $extension;
                    $options = compact('target_dir','file_name');


                    $upload_result = $image_uploader->upload($license_back,$options);
                    if(!$upload_result['status']) {
                        flashMessage('danger', $upload_result['status_message']);
                        return back();
                    }

                    $user_doc->license_back = $target_path.$upload_result['file_name'];
                }
                if($request->hasFile('profile_image')) {
                    $profile_image = $request->file('profile_image');

                    $extension = $profile_image->getClientOriginalExtension();
                    $file_name = "profile_picture_" . $user->id . "." . $extension;
                    $options = compact('target_dir','file_name');

                    $upload_result = $image_uploader->upload($profile_image,$options);
                    if(!$upload_result['status']) {
                        flashMessage('danger', $upload_result['status_message']);
                        return back();
                    }

                 $user_picture->src = $target_path.$upload_result['file_name'];
                }
                if($request->hasFile('abn_number')) {
                    $abn_number = $request->file('abn_number');

                    $extension = $abn_number->getClientOriginalExtension();
                    $file_name = "abn_number_" . $user->id . "." . $extension;
                    $options = compact('target_dir','file_name');


                    $upload_result = $image_uploader->upload($abn_number,$options);
                    if(!$upload_result['status']) {
                        flashMessage('danger', $upload_result['status_message']);
                        return back();
                    }

                    $user_doc->abn_number = $target_path.$upload_result['file_name'];
                }
                if($request->hasFile('right_to_work')) {
                    $right_to_work = $request->file('right_to_work');

                    $extension = $right_to_work->getClientOriginalExtension();
                    $file_name = "right_to_work_" . $user->id . "." . $extension;
                    $options = compact('target_dir','file_name');


                    $upload_result = $image_uploader->upload($right_to_work,$options);
                    if(!$upload_result['status']) {
                        flashMessage('danger', $upload_result['status_message']);
                        return back();
                    }

                    $user_doc->right_to_work = $target_path.$upload_result['file_name'];
                }
                if($request->hasFile('id_document')) {
                    $id_document = $request->file('id_document');

                    $extension = $id_document->getClientOriginalExtension();
                    $file_name = "id_document_" . $user->id . "." . $extension;
                    $document_id_file_name  = $file_name;

                    $document_id_file_path = Storage::disk('local')->putFileAs('doc', $id_document, $document_id_file_name);
                    $document_id_file_path = Storage::disk('local')->getAdapter()->applyPathPrefix($document_id_file_path);
                }
            }
            $user_picture->user_id =$user->id;
            $user_picture->save();
            $user_doc->user_id      = $user->id;
            $user_doc->save();

            if($request->account_num && $request->account_holder && $request->bsb){

                if(!isset($document_id_file_path)){
                    flashMessage('danger', 'Can\'t upload bank details: please choose document ID image');
                            return back();
                }
                $payout_data = (object)[
                    'user_id' => $user->id,
                    'payout_method' => 'stripe',
                    "bsb" => $request->bsb,
                    "account_number" => $request->account_num,
                    "account_holder_name" => $request->account_holder,
                    "address1" => $request->address_line1,
                    "address2" => $request->address_line2,
                    "city" => $request->city,
                    "state" => $request->state,
                    "postal_code" => $request->postal_code,
                    "country" => "AU",
                    "currency" => "AUD",
                    'id_file_path' => $document_id_file_path,
                    'id_file_name' => $document_id_file_name,
                ];

                $upload_result = $this->updatePayoutPreference($payout_data);

                if($upload_result->status_code != 1){
                    flashMessage('danger', 'Can\'t upload bank details: '.$upload_result->status_message);
                            return back();
                }
            }

            flashMessage('success', 'Updated Successfully');
        }
        return redirect(LOGIN_USER_TYPE.'/community_leader');
    }

    /**
     * Delete CommunityLeader
     *
     * @param array $request    Input values
     * @return redirect     to CommunityLeader View
     */
    public function delete(Request $request)
    {
        $result= $this->canDestroy($request->id);

        if($result['status'] == 0) {
            flashMessage('error',$result['message']);
            return back();
        }
        $driver_owe_amount = DriverOweAmount::where('user_id',$request->id)->first();
        if($driver_owe_amount->amount == 0) {
            $driver_owe_amount->delete();
        }
        try {
            User::find($request->id)->delete();
        }
        catch(\Exception $e) {
            $driver_owe_amount = DriverOweAmount::where('user_id',$request->id)->first();
            if($driver_owe_amount == '') {
                DriverOweAmount::create([
                    'user_id' => $request->id,
                    'amount' => 0,
                    'currency_code' => 'USD',
                ]);
            }
            flashMessage('error','Driver have some trips, So can\'t delete this driver');
            // flashMessage('error',$e->getMessage());
            return back();
        }

        flashMessage('success', 'Deleted Successfully');
        return redirect(LOGIN_USER_TYPE.'/community_leader');
    }

    // Check Given User deletable or not
    public function canDestroy($user_id)
    {
        $return  = array('status' => '1', 'message' => '');

        //Company can delete only this company's drivers.
        if(LOGIN_USER_TYPE=='company') {
            $user = User::find($user_id);
            if ($user->company_id != Auth::guard('company')->user()->id) {
                $return = ['status' => 0, 'message' => 'Invalid ID'];
                return $return;
            }
        }

        $driver_trips   = Trips::where('driver_id',$user_id)->count();
        $user_referral  = ReferralUser::where('user_id',$user_id)->orWhere('referral_id',$user_id)->count();

        if($driver_trips) {
            $return = ['status' => 0, 'message' => 'Driver have some trips, So can\'t delete this driver'];
        }
        else if($user_referral) {
            $return = ['status' => 0, 'message' => 'Rider have referrals, So can\'t delete this driver'];
        }
        return $return;
    }

    public function detail($user_id)
    {
        $community_leader = DB::table('users')->select('users.*', 'driver_address.address_line1', 'driver_address.address_line2', 'driver_address.city', 'driver_address.state', 'stripe_subscription_plans.plan_name')
                                ->leftJoin('driver_address', 'driver_address.user_id', '=', 'users.id')
                                ->leftJoin('stripe_subscriptions', 'stripe_subscriptions.user_id', '=', 'users.id')
                                ->leftJoin('stripe_subscription_plans', 'stripe_subscription_plans.id', '=', 'stripe_subscriptions.plan')
                                ->where('users.id', $user_id)
                                ->first();

        $merchants = DB::table('merchants')->select('merchants.id as id', 'merchants.name', 'merchants.created_at', 'driver_address.address_line1', 'driver_address.address_line2', 'driver_address.city', 'driver_address.state')
                        ->leftJoin('users', 'merchants.user_id', '=', 'users.id')
                        ->leftJoin('driver_address', 'driver_address.user_id', '=', 'users.id')
                        ->where('users.used_referral_code', $community_leader->referral_code)
                        ->orderBy('merchants.created_at', 'desc')
                        ->take(10)
                        ->get();

        $drivers = DB::table('users')->select('users.*', 'driver_address.address_line1', 'driver_address.address_line2', 'driver_address.city', 'driver_address.state')
                        ->leftJoin('driver_address', 'driver_address.user_id', '=', 'users.id')
                        ->where('users.user_type', 'Driver')
                        ->where('users.used_referral_code', $community_leader->referral_code)
                        ->orderBy('users.created_at', 'desc')
                        ->take(10)
                        ->get();

        $drivers_ = DB::table('users')
            ->where('user_type', 'Driver')
            ->where('used_referral_code', $community_leader->referral_code)
            ->get();

        $driver_ids = array();
        foreach ($drivers_ as $driver)
        {
            $driver_ids[] = $driver->id;
        }

        $deliveries = DB::table('delivery_orders')->select('delivery_orders.id', 'delivery_orders.fee', 'users.first_name', 'users.last_name', 'request.pickup_location', 'request.drop_location')
                        ->leftJoin('users', 'delivery_orders.driver_id', '=', 'users.id')
                        ->leftJoin('request', 'delivery_orders.ride_request', '=', 'request.id')
                        ->whereIn('delivery_orders.driver_id', $driver_ids)
                        ->where('delivery_orders.status', 'delivered')
                        ->orderBy('delivery_orders.created_at', 'desc')
                        ->take(10)
                        ->get();

        return view('admin.community_leader.detail')
                ->with('community_leader', $community_leader)
                ->with('merchants', $merchants)
                ->with('drivers', $drivers)
                ->with('deliveries', $deliveries);
    }
}
