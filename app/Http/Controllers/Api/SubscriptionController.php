<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Start\Helpers;
use App\Http\Helper\RequestHelper;
use App\Models\DriversSubscriptions;
use App\Models\PaymentMethod;
use App\Models\StripeSubscriptionsPlans;
use App\Models\User;
use App\Http\Controllers\Api\ProfileController;
use Auth;
use App;
use DateTime;
use Session;
use Validator;
use DB;
use JWTAuth;



class SubscriptionController extends Controller
{
    // Global variable for Helpers instance
	protected $request_helper;

    public function __construct(RequestHelper $request)
    {
    	$this->request_helper = $request;
		$this->helper = new Helpers;
        //Set key and api_version from config (PaymentGateway)
        $stripe_skey = payment_gateway('secret','Stripe');
        $api_version = payment_gateway('api_version','Stripe');
        \Stripe\Stripe::setApiKey($stripe_skey);
        \Stripe\Stripe::setApiVersion($api_version);
	}

    /**
     * View subscription information for driver
     *
     * @param array $request  Input values
     * @return Static page view file
     */
	public function index(Request $request) {
        $user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            $subscription = DriversSubscriptions::where('user_id',$user->id)
                ->whereNotIn('status', ['canceled'])
                ->first();

            if($subscription){
                $subscription_plan = StripeSubscriptionsPlans::where('id',$subscription->plan)->first();

                $subscription['plan_id'] = $subscription_plan->plan_id;

                if($subscription_plan->plan_name == 'Driver Only'){
                    $subscription['plan_name'] = $subscription_plan->plan_name;
                    $subscription['plan'] = $subscription_plan->id;
                }
                else{
                    $subscription['plan_name'] = 'Driver Member';
                    //$subscription['plan'] = $subscription_plan->id;
                    $subscription['plan'] = 2;
                }
            }

            $sub_data = array(
                'status_code'		=> '1',
				'status_message'	=> trans('messages.api.listed_successfully'),
                'subscription'      => $subscription,
            );

            return response()->json($sub_data);
        }
    }

    /**
     * Create stripe subscription for driver
     *
     * @param array $request  Input values
     * @return Json
     */
	public function createCustomer(Request $request){

        $user_details = JWTAuth::parseToken()->authenticate();

        $rules = array(
			'country' => 'required',
			'payment_method' => 'required',
		);

		$validator = Validator::make($request->all(), $rules);

		if($validator->fails()) {
            return response()->json([
            	'status_code' => '0',
            	'status_message' => $validator->messages()->first()
            ]);
        }

		$user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            //Set key and api_version from config (PaymentGateway)
            $stripe_skey = payment_gateway('secret','Stripe');
            $api_version = payment_gateway('api_version','Stripe');
            \Stripe\Stripe::setApiKey($stripe_skey);
            \Stripe\Stripe::setApiVersion($api_version);

            $country = $request->country;
            $card_name = $request->card_name;

            $payment_details = PaymentMethod::firstOrNew(['user_id' => $user->id]);

            if($payment_details->customer_id){
                // This creates a new Customer and attaches the default PaymentMethod in one API call.
                $customer = \Stripe\Customer::update(
                    $payment_details->customer_id,
                    [   'email' => $user->email,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'phone' => '+' . $user->country_code . $user->mobile_number,
                        'description' => 'Driver (created by Ride On back-end)',
                        'payment_method' => $request->payment_method,
                        'invoice_settings' => [
                            'default_payment_method' => $request->payment_method,
                        ]
                ]);
            }
            else{
                // This creates a new Customer and attaches the default PaymentMethod in one API call.
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => '+' . $user->country_code . $user->mobile_number,
                    'description' => 'Driver (created by Ride On back-end)',
                    'payment_method' => $request->payment_method,
                    'invoice_settings' => [
                        'default_payment_method' => $request->payment_method,
                    ]
                ]);
            }

            $plan = StripeSubscriptionsPlans::where('plan_name','Driver Member')->first();

            $subscription = \Stripe\Subscription::create([
                'customer' => $customer->id,
                'items' => [
                    [
                        'plan' =>  $plan->plan_id,
                    ],
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            $subscription_row = DriversSubscriptions::where('user_id',$user->id)
                ->whereNotIn('status', ['canceled'])
                ->first();
            if(!$subscription_row){
                $subscription_row = new DriversSubscriptions;
                $subscription_row->user_id      = $user->id;
            }
            $subscription_row->stripe_id    = $subscription->id;
            $subscription_row->status       = 'subscribed';
            $subscription_row->email        = $user_details->email;
            $subscription_row->plan         = $plan->id;
            $subscription_row->country      = $country;
            $subscription_row->card_name    = $user_details->first_name . ' ' . $user_details->last_name;
            $subscription_row->save();


            $sub_data = array(
                'status_code'		=> '1',
				'status_message'	=> trans('messages.subscriptions.subscribed'),
                'subscription'      => $subscription,
            );

            return response()->json($sub_data);
        }
    }

    /**
     * Cancel driver's stripe subscription
     *
     * @param array $request  Input values
     * @return Json
     */
    public function cancelSubscription(Request $request) {
        $user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            $subscription_row = DriversSubscriptions::where('user_id',$user->id)
                ->whereNotIn('status', ['canceled'])
                ->first();

            $subscription = \Stripe\Subscription::retrieve($subscription_row->stripe_id);
            $subscription->cancel();


            if($subscription_row){
                $plan = StripeSubscriptionsPlans::where('id',3)->first();
                $subscription_return['plan_id'] = $plan->plan_id;
                $subscription_return['plan_name'] = $plan->plan_name;
                $subscription_row->status = 'canceled';
                $subscription_row->save();
                $subscription_row = new DriversSubscriptions;
                $subscription_row->user_id      = $user->id;
                $subscription_row->plan = 1;
                $subscription_row->save();
            }
            else{
                $subscription_row = new DriversSubscriptions;
                $subscription_row->user_id      = $user->id;
                $subscription_row->plan = 1;
                $subscription_row->save();
            }

            $sub_data = array(
                'status_code'		=> '1',
				'status_message'	=> trans('messages.subscriptions.cancelled'),
                'subscription'      => $subscription_row,
            );

            return response()->json($sub_data);
        }
    }

    /**
     * Reactivate driver's stripe subscription
     *
     * @param array $request  Input values
     * @return Json
     */
    public function reactivateSubscription(Request $request) {
        $user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            //Set key and api_version from config (PaymentGateway)
            $stripe_skey = payment_gateway('secret','Stripe');
            $api_version = payment_gateway('api_version','Stripe');
            \Stripe\Stripe::setApiKey($stripe_skey);
            \Stripe\Stripe::setApiVersion($api_version);

            $subscription_row = DriversSubscriptions::where('user_id',$user->id)
                ->whereNotIn('status', ['canceled'])
                ->first();

            $plan = StripeSubscriptionsPlans::where('id',$subscription_row->plan)->first();

            $subscription = \Stripe\Subscription::retrieve($subscription_row->stripe_id);
            \Stripe\Subscription::update($subscription_row->stripe_id, [
                'cancel_at_period_end' => false,
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'plan' => $plan->plan_id,
                    ],
                ],
            ]);

            $subscription_row->status = 'subscribed';
            $subscription_row->save();

            if($subscription_row){
                $subscription_row['plan_id'] = $plan->plan_id;
                $subscription_row['plan_name'] = $plan->plan_name;
            }

            $sub_data = array(
                'status_code'		=> '1',
				'status_message'	=> trans('messages.subscriptions.resumed'),
                'subscription'      => $subscription_row,
            );

            return response()->json($sub_data);
        }
    }

    /**
     * Pause driver's stripe subscription
     *
     * @param array $request  Input values
     * @return Json
     */
    public function pauseSubscription(Request $request) {
        $user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            //Set key and api_version from config (PaymentGateway)
            $stripe_skey = payment_gateway('secret','Stripe');
            $api_version = payment_gateway('api_version','Stripe');
            \Stripe\Stripe::setApiKey($stripe_skey);
            \Stripe\Stripe::setApiVersion($api_version);

            $subscription_row = DriversSubscriptions::where('user_id',$user->id)
                ->whereNotIn('status', ['canceled','paused'])
                ->first();

            \Stripe\Subscription::update(
                $subscription_row->stripe_id,
                [
                    'pause_collection' => [
                        'behavior' => 'mark_uncollectible',
                    ],
                ]
            );

            $subscription_row->status = 'paused';
            $subscription_row->save();

            if($subscription_row){
                $plan = StripeSubscriptionsPlans::where('id',$subscription_row->plan)->first();
                $subscription_row['plan_id'] = $plan->plan_id;
                $subscription_row['plan_name'] = $plan->plan_name;
            }

            $sub_data = array(
                'status_code'		=> '1',
				'status_message'	=> trans('messages.subscriptions.paused'),
                'subscription'      => $subscription_row,
            );

            return response()->json($sub_data);
        }
    }

    /**
     * Resume paused driver's stripe subscription
     *
     * @param array $request  Input values
     * @return Json
     */
    public function resumeSubscription(Request $request) {
        $user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            //Set key and api_version from config (PaymentGateway)
            $stripe_skey = payment_gateway('secret','Stripe');
            $api_version = payment_gateway('api_version','Stripe');
            \Stripe\Stripe::setApiKey($stripe_skey);
            \Stripe\Stripe::setApiVersion($api_version);

            $subscription_row = DriversSubscriptions::where('user_id',$user->id)
                ->whereNotIn('status', ['canceled'])
                ->first();

            \Stripe\Subscription::update(
                $subscription_row->stripe_id,
                [
                    'pause_collection' => '',
                ]
            );

            $subscription_row->status = 'subscribed';
            $subscription_row->save();

            if($subscription_row){
                $plan = StripeSubscriptionsPlans::where('id',$subscription_row->plan)->first();
                $subscription_row['plan_id'] = $plan->plan_id;
                $subscription_row['plan_name'] = $plan->plan_name;
            }

            $sub_data = array(
                'status_code'		=> '1',
				'status_message'	=> trans('messages.subscriptions.resumed'),
                'subscription'      => $subscription_row,
            );

            return response()->json($sub_data);
        }
    }


    /**
     * Change driver's stripe subscription
     *
     * @param array $request  Input values
     * @return Json
     */
    public function switchSubscription(Request $request) {
        $user_details = JWTAuth::parseToken()->authenticate();

		$user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            $subscription_row = DriversSubscriptions::where('user_id',$user->id)
                ->whereNotIn('status', ['canceled'])
                ->first();

            $plan = StripeSubscriptionsPlans::where('id',$subscription_row->plan)->first();
            $type = $plan->plan_name;
            switch($type) {
                case "Founder":
                    $plan = StripeSubscriptionsPlans::where('plan_name','Regular')->first();
                break;
                case "Regular":
                    $plan = StripeSubscriptionsPlans::where('plan_name','Founder')->first();
                break;
            }

            $stripe_skey = payment_gateway('secret','Stripe');
            $api_version = payment_gateway('api_version','Stripe');
            \Stripe\Stripe::setApiKey($stripe_skey);
            \Stripe\Stripe::setApiVersion($api_version);

            $subscription = \Stripe\Subscription::retrieve($subscription_row->stripe_id);
            \Stripe\Subscription::update($subscription_row->stripe_id, [
                'cancel_at_period_end' => false,
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'plan' => $plan->plan_id,
                    ],
                ],
            ]);

            $subscription_row->plan     = $plan->id;
            $subscription_row->save();

            if($subscription_row){
                $subscription_row['plan_id'] = $plan->plan_id;
                $subscription_row['plan_name'] = $plan->plan_name;
            }

            $sub_data = array(
                'status_code'		=> '1',
				'status_message'	=> trans('messages.subscriptions.upgraded'),
                'subscription'      => $subscription_row,
            );

            return response()->json($sub_data);
        }
    }

     /**
     * Upgrade driver's stripe subscription
     *
     * @param array $request  Input values
     * @return Json
     */
    public function upgradeSubscription(Request $request) {
        $user_details = JWTAuth::parseToken()->authenticate();

        $user = User::where('id', $user_details->id)->first();

        if(!$user) {
            return response()->json([
				'status_code'		=> '0',
				'status_message'	=> trans('messages.invalid_credentials'),
			]);
        }
        else{
            $stripe_payment = resolve('App\Repositories\StripePayment');

            $payment_details = PaymentMethod::firstOrNew(['user_id' => $user_details->id]);

            if(!isset($payment_details->customer_id)) {
                $stripe_customer = $stripe_payment->createCustomer($user_details->email);
                if($stripe_customer->status == 'failed') {
                    return response()->json([
                        'status_code' 		=> "0",
                        'status_message' 	=> $stripe_customer->status_message,
                    ]);
                }
                $payment_details->customer_id = $stripe_customer->customer_id;
                $payment_details->save();
            }

            $subscription_row = DriversSubscriptions::where('user_id',$user->id)
            ->whereNotIn('status', ['canceled'])
            ->first();

            if(!$subscription_row){
                $subscription_row = new DriversSubscriptions;
                $subscription_row->user_id      = $user->id;
                $subscription_row->plan = 1;
                $subscription_row->save();
            }

            $plan = StripeSubscriptionsPlans::where('id',$subscription_row->plan)->first();
            $type = $plan->plan_name;

            if($type == "Driver Member"){
                return response()->json([
                    'status_code'		=> '0',
                    'status_message'	=> 'You are already a member.',
                ]);
            }
            else{
                if(!$subscription_row->stripe_id){
                    try{
                        $plan = StripeSubscriptionsPlans::where('plan_name','Driver Member')->first();
                        // $payment_method = \Stripe\PaymentMethod::create([
                        //     'type' => 'card',
                        //     'card' => [
                        //     'number' => '4242424242424242',
                        //     'exp_month' => 5,
                        //     'exp_year' => 2021,
                        //     'cvc' => '314',
                        //     ],
                        // ]);
                        // $setup_intent = \Stripe\SetupIntent::create([
                        //     'payment_method_types' 	=> ['card'],
                        //     'customer'				=> $payment_details->customer_id,
                        //     'usage'					=> 'off_session',
                        // ]);
                        // $setup_intent = \Stripe\SetupIntent::update(
                        //     $setup_intent->id,
                        //     ['payment_method' => $payment_method],
                        // );
                        // $setup_intent->confirm([
                        //     'payment_method' => $payment_method->id,
                        // ]);
                        $payment = resolve('App\Http\Controllers\Api\ProfileController');
                        $request->request->add(['intent_id' => $request->payment_method]);
                        $setup_intent = \Stripe\SetupIntent::retrieve(
                            $request->payment_method
                        );
                        $payment->add_card_details($request);

                        $subscription = \Stripe\Subscription::create([
                            'customer' => $payment_details->customer_id,
                            'items' => [
                                [
                                    'plan' =>  $plan->plan_id,
                                ],
                            ],
                            'expand' => ['latest_invoice.payment_intent'],
                            'default_payment_method' => $setup_intent->payment_method
                        ]);
                    }
                    catch(\Exception $e){
                        return response()->json([
                            'status_code'		=> '0',
                            'status_message'	=> $e->getMessage(),
                        ]);
                    }

                    $subscription_row->stripe_id    = $subscription->id;
                    $subscription_row->status       = 'subscribed';
                    $subscription_row->email        = $user_details->email;
                    $subscription_row->plan         = $plan->id;
                    $subscription_row->country      = $request->country;
                    $subscription_row->card_name    = $user_details->first_name . ' ' . $user_details->last_name;
                    $subscription_row->save();

                    if($subscription_row){
                        $subscription_row['plan_id'] = $plan->plan_id;
                        $subscription_row['plan_name'] = $plan->plan_name;
                    }

                    $sub_data = array(
                        'status_code'		=> '1',
                        'status_message'	=> trans('messages.subscriptions.upgraded'),
                        'subscription'      => $subscription_row,
                    );

                }
                else{
                    $stripe_skey = payment_gateway('secret','Stripe');
                    $api_version = payment_gateway('api_version','Stripe');
                    \Stripe\Stripe::setApiKey($stripe_skey);
                    \Stripe\Stripe::setApiVersion($api_version);

                    $subscription = \Stripe\Subscription::retrieve($subscription_row->stripe_id);
                    \Stripe\Subscription::update($subscription_row->stripe_id, [
                        'cancel_at_period_end' => false,
                        'items' => [
                            [
                                'id' => $subscription->items->data[0]->id,
                                'plan' => $plan->plan_id,
                            ],
                        ],
                    ]);

                    $subscription_row->plan     = $plan->id;
                    $subscription_row->save();

                    if($subscription_row){
                        $subscription_row['plan_id'] = $plan->plan_id;
                        $subscription_row['plan_name'] = $plan->plan_name;
                    }

                    $sub_data = array(
                        'status_code'		=> '1',
                        'status_message'	=> trans('messages.subscriptions.upgraded'),
                        'subscription'      => $subscription_row,
                    );

                    
                }

                //Register to GrowSurf
                $referral_service = resolve('App\Services\ReferralPrograms\GrowSurf');
                $response = $referral_service->createUser([
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email
                ]);
                if(!isset($response->errors) && !empty($response)) {
                    DB::table('users')->where('id',$user->id)->update(['growsurf_id' => $response->id]);
                }

                return response()->json($sub_data);
            }
        }
    }
}