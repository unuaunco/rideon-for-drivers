<?php

/**
 * Driver Controller
 *
 * @package     Dev22
 * @subpackage  Controller
 * @category    Driver
 * @author      pardusurbanus@protonmail.com & harsoda.ketan@gmail.com
 * @version     2.2
 * @link        
 */

namespace App\Http\Controllers\Api;

use App;
use App\Http\Controllers\Controller;
use App\Http\Helper\InvoiceHelper;
use App\Http\Helper\RequestHelper;
use App\Http\Start\Helpers;
use App\Models\Affiliate;
use App\Models\Application;
use App\Models\Country;
use App\Models\DriverAddress;
use App\Models\DriverDocuments;
use App\Models\DriversSubscriptions;
use App\Models\Merchant;
use App\Models\PayoutCredentials;
use App\Models\PayoutPreference;
use App\Models\ProfilePicture;
use App\Models\ReferralUser;
use App\Models\ReferrelReward;
use App\Models\StripeSubscriptionsPlans;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\BadOrders;

class JotFormController extends Controller
{
    protected $request_helper; // Global variable for Helpers instance

    public function __construct(RequestHelper $request, InvoiceHelper $invoice_helper)
    {
        $this->request_helper = $request;
        $this->helper = new Helpers;
        $this->invoice_helper = $invoice_helper;
    }

    /**
     * Add a New Driver
     *
     * @param array $request  Input values
     * @return redirect     to Driver view
     */
    public function driver(Request $request)
    {
        $submissionID = $request->submissionID;
        $fieldvalues = $request->rawRequest;
        logger($fieldvalues);
        $obj = json_decode($fieldvalues, true);

        $ccodes = Country::get();

        $country_code = '';
        $last_phone_number = '';
        $phone_number = $obj['q12_mobileNumber'];

        foreach ($ccodes as $ccode) {
            if (substr($phone_number, 0, strlen('+')) == '+') {
                if (substr($phone_number, 1, strlen($ccode->phone_code)) == $ccode->phone_code) {
                    // match
                    $country_code = $ccode->phone_code;
                    $last_phone_number = substr($phone_number, 1 + strlen($country_code));
                    break;
                }
            } else if (strlen($phone_number) > 10) {
                if (substr($phone_number, 0, strlen($ccode->phone_code)) == $ccode->phone_code) {
                    // match
                    $country_code = $ccode->phone_code;
                    $last_phone_number = substr($phone_number, 1 + strlen($country_code));
                    break;
                }
            } else {
                $country_code = '61';
                $last_phone_number = $phone_number;
                break;
            }
        }

        $user = new User;
        $usedRef = 0;

        $user->first_name = $obj['q1_name']['first'];
        $user->last_name = $obj['q1_name']['last'];
        $user->email = $obj['q3_email'];
        $user->country_code = $country_code;
        $user->mobile_number = $last_phone_number;
        $user->password = $this->randomPassword();
        $user->status = 'Pending';
        $user->user_type = 'Driver';

        if ($usedRef) {
            $user->used_referral_code = 0;
        }
        //$usedRef->referral_code;
        else {
            $user->used_referral_code = 0;
        }

        $user->company_id = 1;
        if ($user->save()) {
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

        //find user by refferer_id
        if ($usedRef) {
            //if there is no reference between users, create it
            $referrel_user = new ReferralUser;
            $referrel_user->referral_id = $user->id;
            $referrel_user->user_id = $usedRef->id;
            $referrel_user->user_type = $usedRef->user_type;
            $referrel_user->save();
        }

        $user_pic = new ProfilePicture;
        $user_pic->user_id = $user->id;
        if (isset($obj['temp_upload']['q131_profilephoto'][0])) {
            $jotform_image = 'images/users/JotForm/GET STARTED/' . $submissionID . '/' . $obj['temp_upload']['q131_profilephoto'][0];
            $target_path = 'images/users/' . $user->id . "/profile_picture_" . $obj['temp_upload']['q131_profilephoto'][0];
            try {
                $upload_result = Storage::disk('do_spaces')->move($jotform_image, $target_path);
                $user_pic->src = Storage::disk('do_spaces')->url($target_path);
                Storage::disk('do_spaces')->setVisibility($target_path, 'public');
            } catch (\Exception $e) {
                $user_pic->src = 'https://rideon-cdn.sgp1.cdn.digitaloceanspaces.com/images/users/JotForm/GET STARTED/' . $submissionID . '/' . $obj['temp_upload']['q131_profilephoto'][0];
            }
        }
        $user_pic->photo_source = 'Local';
        $user_pic->save();

        $user_address = new DriverAddress;
        $user_address->user_id = $user->id;
        $user_address->address_line1 = $obj['q5_address']['addr_line1'] ? $obj['q5_address']['addr_line1'] : '';
        $user_address->address_line2 = $obj['q5_address']['addr_line2'] ? $obj['q5_address']['addr_line2'] : '';
        $user_address->city = $obj['q5_address']['city'] ? $obj['q5_address']['city'] : '';
        $user_address->state = $obj['q5_address']['state'] ? $obj['q5_address']['state'] : '';
        $user_address->postal_code = $obj['q5_address']['postal'] ? $obj['q5_address']['postal'] : '';
        $user_address->save();

        // TODO: add q6.typeof

        $payout_preference = PayoutPreference::firstOrNew(['user_id' => $user->id, 'payout_method' => "Stripe"]);
        $payout_preference->user_id = $user->id;
        $payout_preference->country = "IN";
        $payout_preference->account_number = $obj['q10_bankAccount']['field_4'];
        $payout_preference->holder_name = $obj['q10_bankAccount']['field_2'];
        $payout_preference->holder_type = "company";
        $payout_preference->paypal_email = $obj['q10_bankAccount']['field_4'];

        $payout_preference->phone_number = $obj['q12_mobileNumber'] ?? '';
        $payout_preference->branch_code = '';
        $payout_preference->bank_name = '';
        $payout_preference->bank_location = '';
        $payout_preference->payout_method = "Stripe";
        $payout_preference->address_kanji = json_encode([]);
        $payout_preference->save();

        $payout_credentials = PayoutCredentials::firstOrNew(['user_id' => $user->id, 'type' => "Stripe"]);
        $payout_credentials->user_id = $user->id;
        $payout_credentials->preference_id = $payout_preference->id;
        $payout_credentials->payout_id = $obj['q10_bankAccount']['field_4'];
        $payout_credentials->type = "Stripe";
        $payout_credentials->default = 'yes';
        $payout_credentials->save();

        $user_doc = new DriverDocuments;
        $user_doc->user_id = $user->id;
        if (isset($obj['temp_upload']['q7_passportOr'][0])) {
            $jotform_image = 'images/users/JotForm/GET STARTED/' . $submissionID . '/' . $obj['temp_upload']['q7_passportOr'][0];
            $target_path = 'images/users/' . $user->id . "/license_front_" . $obj['temp_upload']['q7_passportOr'][0];
            try {
                $upload_result = Storage::disk('do_spaces')->move($jotform_image, $target_path);
                $user_doc->license_front = Storage::disk('do_spaces')->url($target_path);
                Storage::disk('do_spaces')->setVisibility($target_path, 'public');
            } catch (\Exception $e) {
                $user_doc->license_front = 'https://rideon-cdn.sgp1.cdn.digitaloceanspaces.com/images/users/JotForm/GET STARTED/' . $submissionID . '/' . $obj['temp_upload']['q7_passportOr'][0];
            }
        }
        if (isset($obj['temp_upload']['q7_passportOr'][1])) {
            $jotform_image = 'images/users/JotForm/GET STARTED/' . $submissionID . '/' . $obj['temp_upload']['q7_passportOr'][1];
            $target_path = 'images/users/' . $user->id . "/license_back_" . $obj['temp_upload']['q7_passportOr'][1];
            try {
                $upload_result = Storage::disk('do_spaces')->move($jotform_image, $target_path);
                $user_doc->license_back = Storage::disk('do_spaces')->url($target_path);
                Storage::disk('do_spaces')->setVisibility($target_path, 'public');
            } catch (\Exception $e) {
                $user_doc->license_back = 'https://rideon-cdn.sgp1.cdn.digitaloceanspaces.com/images/users/JotForm/GET STARTED/' . $submissionID . '/' . $obj['temp_upload']['q7_passportOr'][1];
            }

        }
        // $user_doc->abn_number = $obj['q136_yourAbn'];
        $user_doc->save();

        $plan = StripeSubscriptionsPlans::where('plan_name', 'Driver only')->first();
        $subscription_row = new DriversSubscriptions;
        $subscription_row->user_id = $user->id;
        $subscription_row->stripe_id = '';
        $subscription_row->status = 'subscribed';
        $subscription_row->email = $user->email;
        $subscription_row->plan = $plan->id;
        $subscription_row->country = '';
        $subscription_row->card_name = '';
        $subscription_row->save();

        $application = new Application;
        $target_path = 'images/users/JotForm/GET STARTED/' . $submissionID . '/' . $submissionID . '.pdf';
        $application->pdf = Storage::disk('do_spaces')->url($target_path);
        Storage::disk('do_spaces')->setVisibility($target_path, 'public');
        $application->user_id = $user->id;
        $application->type = 'Driver';
        $application->vehicleType = implode($obj['q6_typeOf']);
        $application->save();

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Validation success.',
        ]);
    }

    /**
     * Add a New affiliate
     *
     * @param array $request  Input values
     * @return redirect     to affiliate view
     */
    public function affiliate(Request $request)
    {
        $submissionID = $request->submissionID;
        $fieldvalues = $request->rawRequest;
        $obj = json_decode($fieldvalues, true);

        $trading_name = $obj['q147_ltstronggtcompanytradingNameltstronggt'];
        $country = $obj['q144_ltstronggtaddressltstronggt']['country'];
        $payment_details = $obj['q145_ltstronggtpaymentDetailsltstronggt'];

        $user = new User;
        //$usedRef = User::where('referral_code', $obj['q138_invitationCode'])->first();

        $user->first_name = $obj['q142_contactPerson']['first'];
        $user->last_name = $obj['q142_contactPerson']['last'];
        $user->email = $obj['q128_email'];
        $user->country_code = $obj['q143_phoneNumber']['area'];
        $user->mobile_number = $obj['q143_phoneNumber']['phone'];
        //$user->password     = $this->randomPassword();
        $user->status = 'Pending';
        $user->user_type = 'Affiliate';

        // if ($usedRef)
        //     $user->used_referral_code = $usedRef->referral_code;
        // else
        //     $user->used_referral_code = 0;

        $user->company_id = 1;
        if ($user->save()) {
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

        //find user by refferer_id
        // if($usedRef) {
        //     //if there is no reference between users, create it
        //     $referrel_user = new ReferralUser;
        //     $referrel_user->referral_id = $user->id;
        //     $referrel_user->user_id     = $usedRef->id;
        //     $referrel_user->user_type   = $usedRef->user_type;
        //     $referrel_user->save();
        // }

        $affiliate_data = new Affiliate;
        $affiliate_data->user_id = $user->id;
        $affiliate_data->trading_name = $trading_name;
        $affiliate_data->save();

        $user_address = new DriverAddress;
        $user_address->user_id = $user->id;
        $user_address->address_line1 = $obj['q144_ltstronggtaddressltstronggt']['addr_line1'];
        $user_address->address_line2 = $obj['q144_ltstronggtaddressltstronggt']['addr_line2'];
        $user_address->city = $obj['q144_ltstronggtaddressltstronggt']['city'];
        $user_address->state = $obj['q144_ltstronggtaddressltstronggt']['state'];
        $user_address->postal_code = $obj['q144_ltstronggtaddressltstronggt']['postal'];
        $user_address->save();

        // $payout_preference = PayoutPreference::firstOrNew(['user_id' => $user->id,'payout_method' => "BankTransfer"]);
        // $payout_preference->user_id = $user->id;
        // $payout_preference->country = "IN";
        // $payout_preference->account_number  = $obj['q137_yourBank']['field_2'];
        // $payout_preference->holder_name     = $obj['q137_yourBank']['field_3'];
        // $payout_preference->holder_type     = "company";
        // $payout_preference->paypal_email    = $obj['q137_yourBank']['field_2'];

        // $payout_preference->phone_number    = $obj['q127_mobileNumber'] ?? '';
        // $payout_preference->branch_code     = $obj['q137_yourBank']['field_5'] ?? '';
        // $payout_preference->bank_name       = $obj['q137_yourBank']['field_6'] ?? '';
        // $payout_preference->bank_location   = $obj['q137_yourBank']['field_4'] ?? '';
        // $payout_preference->payout_method   = "BankTransfer";
        // $payout_preference->address_kanji   = json_encode([]);
        // $payout_preference->save();

        // $payout_credentials = PayoutCredentials::firstOrNew(['user_id' => $user->id,'type' => "BankTransfer"]);
        // $payout_credentials->user_id = $user->id;
        // $payout_credentials->preference_id = $payout_preference->id;
        // $payout_credentials->payout_id = $obj['q137_yourBank']['field_2'];
        // $payout_credentials->type = "BankTransfer";
        // $payout_credentials->default = 'yes';
        // $payout_credentials->save();

        // $application = new Application;
        // $application->pdf = 'https://rideon-cdn.sgp1.cdn.digitaloceanspaces.com/images/users/JotForm/Affiliate Application/' . $submissionID . '/' . $submissionID . '.pdf';
        // $application->user_id = $user->id;
        // $application->type = 'Affiliate';
        // $application->vehicleType = implode($obj['q132_vehicleType']);
        // $application->save();

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Validation success.',
        ]);
    }

    /**
     * Add a New Merchant
     *
     * @param array $request  Input values
     * @return redirect     to Home Delivery Order view
     */
    public function merchant(Request $request, $id = null)
    {
        $submissionID = $request->submissionID;
        $fieldvalues = $request->rawRequest;
        $obj = json_decode($fieldvalues, true);
        $ccodes = Country::get();

        $country_code = '';
        $last_phone_number = '';
        $phone_number = $obj['q127_mobileNumber'];

        foreach ($ccodes as $ccode) {
            if (substr($phone_number, 1, strlen($ccode->phone_code)) == $ccode->phone_code) {
                // match
                $country_code = $ccode->phone_code;
                $last_phone_number = substr($phone_number, 1 + strlen($country_code));
                break;
            }
        }

        $user = new User;
        $usedRef = User::where('referral_code', $obj['q136_invitationCode'])->first();

        $user->first_name = $obj['q96_name']['first'];
        $user->last_name = $obj['q96_name']['last'];

        if ($usedRef) {
            $user->used_referral_code = $usedRef->referral_code;
        } else {
            $user->used_referral_code = 0;
        }

        $user->email = $obj['q128_email'];
        $user->country_code = $country_code;
        $user->mobile_number = $last_phone_number;
        $user->user_type = 'Merchant';
        $user->status = 'Pending';

        if ($user->save()) {
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

        //find user by refferer_id
        if ($usedRef) {
            //if there is no reference between users, create it
            $referrel_user = new ReferralUser;
            $referrel_user->referral_id = $user->id;
            $referrel_user->user_id = $usedRef->id;
            $referrel_user->user_type = $usedRef->user_type;
            $referrel_user->save();
        }

        $user_address = new DriverAddress;

        $user_address->user_id = $user->id;
        $user_address->address_line1 = $obj['q129_address']['addr_line1'];
        $user_address->address_line2 = $obj['q129_address']['addr_line2'];
        $user_address->city = $obj['q129_address']['city'];
        $user_address->state = $obj['q129_address']['state'];
        $user_address->postal_code = $obj['q129_address']['postal'];
        $user_address->save();

        $merchant = new Merchant;

        $merchant->user_id = $user->id;
        $merchant->name = $obj['q134_trading_name'];
        $merchant->description = $obj['q149_description'];
        $merchant->cuisine_type = $obj['q135_cuisine_type'];
        switch ($obj['q150_integration_type']) {
            case 'Gloria Food':
                $merchant->integration_type = 1;
                break;
            case 'SquareUp':
                $merchant->integration_type = 2;
                break;
            case 'Shopify':
                $merchant->integration_type = 3;
                break;
        }
        $merchant->delivery_fee = $obj['q148_fee']['field_1'];
        $merchant->delivery_fee_per_km = $obj['q148_fee']['field_2'];
        $merchant->delivery_fee_base_distance = $obj['q148_fee']['field_3'];
        switch ($merchant->integration_type) {
            case 1: // Gloria Food
                $merchant->shared_secret = Str::uuid();
                break;
        }
        $merchant->save();

        $application = new Application;
        $target_path = 'images/users/JotForm/Merchant Application/' . $submissionID . '/' . $submissionID . '.pdf';
        $application->pdf = Storage::disk('do_spaces')->url($target_path);
        Storage::disk('do_spaces')->setVisibility($target_path, 'public');
        $application->user_id = $user->id;
        $application->type = 'Merchant';
        $application->q_hear = $obj['q145_q_hear'];
        $application->q_popularItem = $obj['q146_q_popularItem'];
        $i = 1;
        $cnt = sizeof($obj['q139_q_expectOrders']);
        foreach ($obj['q139_q_expectOrders'] as $tmp) {
            $application->q_expectOrders .= $tmp[0];
            if ($i != $cnt) {
                $application->q_expectOrders .= ',';
            }

            $i++;
        }
        $application->asset_website = $obj['q144_q_assets']['field_2'];
        $application->asset_facebook = $obj['q144_q_assets']['field_1'];
        $application->asset_instagram = $obj['q144_q_assets']['field_3'];
        $application->asset_other = $obj['q144_q_assets']['field_4'];

        if (isset($obj['temp_upload']['q141_logo'][0])) {
            $target_path = 'images/users/JotForm/Merchant Application/' . $submissionID . '/' . $obj['temp_upload']['q141_logo'][0];
            $application->logo = Storage::disk('do_spaces')->url($target_path);
            Storage::disk('do_spaces')->setVisibility($target_path, 'public');
            //$application->logo = 'https://rideon-cdn.sgp1.cdn.digitaloceanspaces.com/images/users/JotForm/Merchant Application/' . $submissionID . '/' . $obj['temp_upload']['q141_logo'][0];
        }

        if (isset($obj['temp_upload']['q147_q_photoItem'][0])) {
            $target_path = 'images/users/JotForm/Merchant Application/' . $submissionID . '/' . $obj['temp_upload']['q147_q_photoItem'][0];
            $application->pdf = Storage::disk('do_spaces')->url($target_path);
            Storage::disk('do_spaces')->setVisibility($target_path, 'public');
            //$application->photoItem = 'https://rideon-cdn.sgp1.cdn.digitaloceanspaces.com/images/users/JotForm/Merchant Application/' . $submissionID . '/' . $obj['temp_upload']['q147_q_photoItem'][0];
        }

        $application->save();

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Validation success.',
        ]);
    }

    public function randomPassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 16; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    /**
     * Add a New Driver
     *
     * @param array $request  Input values
     * @return redirect     to Driver view
     */
    public function growSurfParticipant(Request $request)
    {
        $fieldvalues = $request->rawRequest;
        logger($fieldvalues);
        $obj = json_decode($fieldvalues, true);

        $campaign = '';
        $referredBy = '';
        if (strpos($obj['q19_parentPageUrl'], "https://www.joinrideon.com/apply?grsf=") === 0) {
            $campaign = 'ny82jr';
            $referredBy = str_replace("https://www.joinrideon.com/apply?grsf=", "", $obj['q19_parentPageUrl']);
        } elseif (strpos($obj['q19_parentPageUrl'], "https://www.autonomus.co/apply?grsf=") === 0) {
            $campaign = '2jjd2o';
            $referredBy = str_replace("https://www.autonomus.co/apply?grsf=", "", $obj['q19_parentPageUrl']);
        } else {
            return response()->json([
                'status_code' => '0',
                'status_message' => 'Validation unsuccessful.',
            ]);
        }

        if ($obj['q17_doYou17'] == 'MEMBER OWNER') {
            //Register to GrowSurf
            $referral_service = resolve('App\Services\ReferralPrograms\GrowSurf');
            $response = $referral_service->createUser(
                [
                    'firstName' => $obj['q1_name']['first'],
                    'lastName' => $obj['q1_name']['last'],
                    'email' => $obj['q3_email'],
                    'referredBy' => $referredBy,
                ],
                $campaign
            );
        }

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Validation success.',
        ]);
    }

    // https://dev.rideon.co/api/integrations/jotform_growsurf_reward
    public function doReferrePayment()
    {
        $input = @file_get_contents("php://input");
        $event_json = json_decode($input);
        // print_r($event_json);die;
        switch ($event_json->event) {
            // case 'NEW_PARTICIPANT_ADDED':
            case 'PARTICIPANT_REACHED_A_GOAL':
                if (!empty($event_json->reward)) {
                    $event_data = $event_json->reward;
                    $referrerId = $event_data->referrerId;
                    if (!empty($referrerId)) {
                        $objUser = User::selectRaw('email')->where('growsurf_id', $referrerId)->first();
                        if (!empty($objUser->email)) {
                            $verifyEmail = ReferrelReward::where('email', $objUser->email)->first();
                            if (!empty($verifyEmail)) {
                                ReferrelReward::where('email', $objUser->email)->increment('sum');
                            } else {
                                $objReferrelReward = new ReferrelReward();
                                $objReferrelReward->email = $objUser->email;
                                $objReferrelReward->sum = 1;
                                $objReferrelReward->created_at = date('Y-m-d H:i:s');
                                $objReferrelReward->updated_at = date('Y-m-d H:i:s');
                                $objReferrelReward->save();
                            }
                        }
                    }
                }
                break;
        }

    }

    /**
     * Add a New Merchant Account
     *
     * @param array $request  Input values
     * @return redirect     to Home Delivery Order view
     */
    public function merchantAccount(Request $request, $id = null)
    {
        $fieldvalues = $request->rawRequest;
        $obj = json_decode($fieldvalues, true);
        try{
            $bad_order = new BadOrders;
            $bad_order->secret = $server_key;
            $bad_order->description = json_encode($obj);
            $bad_order->save();
        }
        catch (\Exception $e) {

        }

        $ccodes = Country::get();

        $country_code = '';
        $last_phone_number = '';
        $phone_number = $obj['q19_contactNumber'];

        foreach ($ccodes as $ccode) {
            if (substr($phone_number, 0, strlen('+')) == '+') {
                if (substr($phone_number, 1, strlen($ccode->phone_code)) == $ccode->phone_code) {
                    // match
                    $country_code = $ccode->phone_code;
                    $last_phone_number = substr($phone_number, 1 + strlen($country_code));
                    break;
                }
            } else if (strlen($phone_number) > 10) {
                if (substr($phone_number, 0, strlen($ccode->phone_code)) == $ccode->phone_code) {
                    // match
                    $country_code = $ccode->phone_code;
                    $last_phone_number = substr($phone_number, 1 + strlen($country_code));
                    break;
                }
            } else {
                $country_code = '61';
                $last_phone_number = $phone_number;
                break;
            }
        }

        $user = new User;
        $user->first_name = $obj['q1_contactPerson']['first'];
        $user->last_name = $obj['q1_contactPerson']['last'];
        $user->email = $obj['q3_email'];
        $user->country_code = $country_code;
        $user->mobile_number = $last_phone_number;
        $user->user_type = 'Merchant';
        $user->status = 'Pending';
        $user->save();

        $user_address = new DriverAddress;
        $user_address->user_id = $user->id;
        $user_address->address_line1 = $obj['q5_addressOf']['addr_line1'] ? $obj['q5_addressOf']['addr_line1'] : '';
        $user_address->address_line2 = $obj['q5_addressOf']['addr_line2'] ? $obj['q5_addressOf']['addr_line2'] : '';
        $user_address->city = $obj['q5_addressOf']['city'] ? $obj['q5_addressOf']['city'] : '';
        $user_address->state = $obj['q5_addressOf']['state'] ? $obj['q5_addressOf']['state'] : '';
        $user_address->postal_code = $obj['q5_addressOf']['postal'] ? $obj['q5_addressOf']['postal'] : '';
        $user_address->save();

        $merchant = new Merchant;
        $merchant->user_id = $user->id;
        $merchant->name = $obj['q12_tradingName'];
        $q15_whenDo = implode('-', $obj['q15_whenDo']);
        $description = $obj['q14_whatPos'] . ', ' . $obj['q16_whatIs'] . ', ' . $q15_whenDo . ', ' . $obj['q20_whoReferred'];
        $merchant->description = $description;
        $merchant->cuisine_type = implode(', ', $obj['q6_typeOf']);
        $merchant->integration_type = 1;
        $merchant->shared_secret = Str::uuid();
        $merchant->delivery_fee = 8.95;
        $merchant->delivery_fee_per_km = 1.00;
        $merchant->delivery_fee_base_distance = 5.00;
        $merchant->save();

        $application = new Application;
        $application->user_id = $user->id;
        $application->type = 'Merchant';
        $application->save();

        return response()->json([
            'status_code' => '1',
            'status_message' => 'Validation success.',
        ]);
    }
}
