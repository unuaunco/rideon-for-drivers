<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\DataTables\AffiliateDataTable;
use App\Models\User;
use App\Models\Affiliate;
use App\Models\Trips;
use App\Models\DriverAddress;
use App\Models\DriverDocuments;
use App\Models\DriversSubscriptions;
use App\Models\StripeSubscriptionsPlans;
use App\Models\Country;
use App\Models\Currency;
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

class AffiliateController extends Controller
{
    protected $helper;  // Global variable for instance of Helpers

    public function __construct()
    {
        $this->helper = new Helpers;
        $this->otp_helper = resolve('App\Http\Helper\OtpHelper');
    }


    /**
     * Load Datatable for Affiliate user
     *
     * @param array $dataTable  Instance of Affiliate user DataTable
     * @return datatable
     */
    public function index(AffiliateDataTable $dataTable)
    {
        return $dataTable->render('admin.affiliate.view');
    }

    /**
     * Add a New Affiliate user
     *
     * @param array $request  Input values
     * @return redirect     to Affiliate user view
     */
    public function add(Request $request)
    {
        if($request->isMethod("GET")) {
            //Inactive Company could not add Affiliate user
            if (LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }
            $data['country_code_option']=Country::select('long_name','phone_code')->get();
            $data['country_name_option']=Country::pluck('long_name', 'short_name');
            $data['company']=Company::where('status','Active')->pluck('name','id');
            return view('admin.affiliate.add',$data);
        }

        if($request->submit) {
            // Add Affiliate Validation Rules
            $rules = array(
                'first_name'    => 'required',
                'last_name'     => 'required',
                'email'         => 'required|email',
                'mobile_number' => 'required|regex:/[0-9]{6}/',
                'password'      => 'required',
                'country_code'  => 'required',
                'user_type'     => 'required',
                'status'        => 'required',
            );

            // Add Affiliate Validation Custom Names
            $attributes = array(
                'first_name'    => trans('messages.user.firstname'),
                'last_name'     => trans('messages.user.lastname'),
                'email'         => trans('messages.user.email'),
                'password'      => trans('messages.user.paswrd'),
                'country_code'  => trans('messages.user.country_code'),
                'user_type'     => trans('messages.user.user_type'),
                'status'        => trans('messages.driver_dashboard.status'),
            );
                // Edit Affiliate Validation Custom Fields message
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
            $user->company_id       = 1;
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

            $affiliate_data = new Affiliate;
            $affiliate_data->user_id = $user->id;
            $affiliate_data->trading_name = $request->trading_name;
            $affiliate_data->save();

            $user_address = new DriverAddress;
            $user_address->user_id       =   $user->id;
            $user_address->address_line1 =   $request->address_line1 ? $request->address_line1 :'';
            $user_address->address_line2 =   $request->address_line2 ? $request->address_line2:'';
            $user_address->city          =   $request->city ? $request->city:'';
            $user_address->state         =   $request->state ? $request->state:'';
            $user_address->postal_code   =   $request->postal_code ? $request->postal_code:'';
            $user_address->save();

            // if ($user->company_id != null && $user->company_id != 1) {
            //     $payout_preference = PayoutPreference::firstOrNew(['user_id' => $user->id,'payout_method' => "BankTransfer"]);
            //     $payout_preference->user_id = $user->id;
            //     $payout_preference->country = "IN";
            //     $payout_preference->account_number  = $request->account_number;
            //     $payout_preference->holder_name     = $request->account_holder_name;
            //     $payout_preference->holder_type     = "company";
            //     $payout_preference->paypal_email    = $request->account_number;

            //     $payout_preference->phone_number    = $request->mobile_number ?? '';
            //     $payout_preference->branch_code     = $request->bank_code ?? '';
            //     $payout_preference->bank_name       = $request->bank_name ?? '';
            //     $payout_preference->bank_location   = $request->bank_location ?? '';
            //     $payout_preference->payout_method   = "BankTransfer";
            //     $payout_preference->address_kanji   = json_encode([]);
            //     $payout_preference->save();

            //     $payout_credentials = PayoutCredentials::firstOrNew(['user_id' => $user->id,'type' => "BankTransfer"]);
            //     $payout_credentials->user_id = $user->id;
            //     $payout_credentials->preference_id = $payout_preference->id;
            //     $payout_credentials->payout_id = $request->account_number;
            //     $payout_credentials->type = "BankTransfer";
            //     $payout_credentials->default = 'yes';

            //     $payout_credentials->save();
            // }

            flashMessage('success', trans('messages.user.add_success'));

            return redirect(LOGIN_USER_TYPE.'/affiliate');
        }

        return redirect(LOGIN_USER_TYPE.'/affiliate');
    }

    /**
     * Update affiliate Details
     *
     * @param array $request    Input values
     * @return redirect     to affiliate View
     */
    public function update(Request $request)
    {
        if($request->isMethod("GET")) {
            $data['result']             = User::find($request->id);

            //If login user is company then company can edit only that company's driver details
            if($data['result'] && (LOGIN_USER_TYPE!='company' || Auth::guard('company')->user()->id == $data['result']->company_id)) {
                $data['address']            = DriverAddress::where('user_id',$request->id)->first();
                $data['affiliate_data'] = Affiliate::where('user_id',$request->id)->first();
                $data['country_code_option']=Country::select('long_name','phone_code')->get();
                $data['company']=Company::where('status','Active')->pluck('name','id');

                $usedRef = User::where('referral_code', $data['result']->used_referral_code)->first();
                if($usedRef){
                    $data['referrer'] = $usedRef->id;
                }
                else{
                    $data['referrer'] = null;
                }


                return view('admin.affiliate.edit', $data);
            }

            flashMessage('danger', 'Invalid ID');
            return redirect(LOGIN_USER_TYPE.'/affiliate');
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
                'country_code'  => 'required',
            );


            // Edit Driver Validation Custom Fields Name
            $attributes = array(
                'first_name'    => trans('messages.user.firstname'),
                'last_name'     => trans('messages.user.lastname'),
                'email'         => trans('messages.user.email'),
                'status'        => trans('messages.driver_dashboard.status'),
                'mobile_number' => trans('messages.profile.phone'),
                'country_code'   => trans('messages.user.country_code'),
            );

            // Edit Rider Validation Custom Fields message
            $messages = array(
                'required'            => ':attribute is required.',
                'mobile_number.regex' => trans('messages.user.mobile_no'),
            );

            $validator = Validator::make($request->all(), $rules, $messages, $attributes);
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


            $user->save();

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

            $affiliate_data = Affiliate::where('user_id',  $user->id)->first();
            if(!$affiliate_data) {
                $affiliate_data = new Affiliate;
            }
            $affiliate_data->user_id = $user->id;
            $affiliate_data->trading_name = $request->trading_name;
            $affiliate_data->save();

            flashMessage('success', 'Updated Successfully');
        }
        return redirect(LOGIN_USER_TYPE.'/affiliate');
    }

        /**
     * Delete Affiliate user
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
        try {
            Affiliate::where('user_id',$request->id)->first()->delete();
            DriverAddress::where('user_id',$request->id)->first()->delete();
            User::find($request->id)->delete();
        }
        catch(\Exception $e) {
            flashMessage('error','Can\'t delete this Affiliate.');
            return back();
        }

        flashMessage('success', 'Deleted Successfully');
        return redirect(LOGIN_USER_TYPE.'/affiliate');
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

        $user_referral  = ReferralUser::where('user_id',$user_id)->orWhere('referral_id',$user_id)->count();

        if($user_referral) {
            $return = ['status' => 0, 'message' => 'Affiliate have referrals, So can\'t delete.'];
        }
        return $return;
    }

            /**
     * Import merchants from csv
     *
     * @param array $request  csv file
     * @return redirect     to Import merchants view
     */
    public function import_affiliates(Request $request)
    {
        if (!$_POST) {
            return view('admin.imports.import_affiliate.import');
        } else {


            if ($request->input('submit') != null) {

                $file = $request->file('file');
                if($file){

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

                        while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
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
                            if (isset($importData[0])){

                                $referral_code = $importData[0];
                                $trading_name = $importData[1];
                                $first_name = $importData[2];
                                $last_name = $importData[3];
                                $email = $importData[4];

                                $pattern = "/\(\+*[0-9]+?\)\s/i";
                                $mobile_number =  preg_replace($pattern, "", $importData[5]);

                                $address_line1 = isset($importData[6]) ? $importData[6] : '';
                                $address_line2 = isset($importData[7]) ? $importData[7] : '';
                                $city = isset($importData[8]) ? $importData[8] : '';
                                $state = isset($importData[9]) ? $importData[9] : '';
                                $postal_code = isset($importData[10]) ? $importData[10] : '';
                                $country = isset($importData[11]) ? $importData[11] : 'Australia';

                                $country_code = Country::where('long_name', $country)->first() ? Country::where('long_name', $country)->first()->phone_code : '61';

                                $user_data = null;

                                $user_count = User::where('email', $email)->count();

                                $user_data = null;

                                $address_data = [
                                    'address_line1' => $address_line1,
                                    'address_line2' => $address_line2,
                                    'city' => $city,
                                    'state' => $state,
                                    'postal_code' => $postal_code
                                ];

                                if($user_count){
                                    $user_data = [
                                        'first_name' => $first_name,
                                        'last_name' => $last_name,
                                        'email' => $email,
                                        "mobile_number" => $mobile_number,
                                        "country_code" =>  $country_code,
                                        'referral_code' => $referral_code,
                                        'user_type' => "Affiliate"
                                    ];
                                }
                                else{
                                    $user_data = [
                                        "first_name" => $first_name,
                                        "last_name" => $last_name,
                                        "email" => $email,
                                        "country_code" => $country_code,
                                        "mobile_number" => $mobile_number,
                                        "password" => bin2hex(openssl_random_pseudo_bytes(8, $crypto)),
                                        "user_type" => "Affiliate",
                                        "company_id" => 1,
                                        "status" => 'Pending',
                                        'referral_code' => $referral_code
                                    ];
                                }

                                $user = User::where('email', $email)->where('user_type',"Affiliate")->first();
                                if(!$user){
                                    $user = new User;
                                    $user->user_type = 'Affiliate';
                                    $user->save();
                                }

                                User::where('id', $user->id)->update($user_data);

                                DriverAddress::updateOrCreate(['user_id' => $user->id], $address_data);

                                Affiliate::updateOrCreate(['trading_name' => $trading_name, 'user_id' => $user->id]);

                                $users_inserted += 1;
                            }
                        }

                        //Send response
                        $this->helper->flash_message('success', 'Succesfully imported: '.$users_inserted.' users'); // Call flash message function

                        return redirect(LOGIN_USER_TYPE . '/affiliate');
                    } else {
                        //Send response
                        $this->helper->flash_message('danger', 'Invalid file type'); // Call flash message function

                        return redirect(LOGIN_USER_TYPE . '/affiliate');
                    }
                }
                else {
                        //Send response
                        $this->helper->flash_message('danger', 'Invalid file'); // Call flash message function

                        return redirect(LOGIN_USER_TYPE . '/affiliate');
                    }
            }
        }
    }
}
