<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;

use App\Repositories\Frontend\Auth\UserRepository;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Http\Controllers\Traits\FileUploadTrait;

use App\Helpers\Payments\Stripe\StripeWrapper;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Cart;
use Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class UserAPIController extends Controller
{
    use FileUploadTrait;
    use SendsPasswordResetEmails;

    private $stripeWrapper;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        
        $this->stripeWrapper = new StripeWrapper();

    }

    function artisan() {

        // Artisan::call('passport:install');

        // // Artisan::call('passport:client --personal');
        // // Artisan::call('cache:clear');
        // // Artisan::call('config:clear');
        // // Artisan::call('view:clear');
        // // Artisan::call('route:clear');
        // // Artisan::call('config:cache');
        Artisan::call('storage:link');

        dd("done");
    } 

    // function show(Request $request)
    // {
    //     if ( auth()->user() ) {
    //         // Authentication passed...
    //         $user = auth()->user();
    //         return $this->sendResponse($user, 'User retrieved successfully');
    //     }

    //     return $this->sendResponse([
    //         'error' => 'Unauthenticated user',
    //         'code' => 401,
    //     ], 'User not logged');

    // }

    // function login(Request $request)
    // {
    //     if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
    //         // Authentication passed...
    //         $user = auth()->user();
            
    //         if (!$user->hasRole('client') && !$user->hasRole('driver')){
    //             return $this->sendResponse([
    //                 'error' => 'Unauthorised user',
    //                 'code' => 401,
    //             ], 'User not Client');
    //         }

    //         $userObj = User::where('id',$user->id)->with('driver')->first();
    //         $hasDriver = ($userObj->driver)?true:false;
    //         $user->device_token = $request->input('device_token', '');

    //         if($user->api_token == null){
    //             $user->api_token = Str::random(60);
    //         } 

    //         $user->save();
    //         $roles = auth()->user()->roles()->select('name')->get();
    //         $rolesArray = [];
            
    //         foreach($roles as $role) array_push($rolesArray, $role->name);
            
    //         //NAO REMOVER O UNSET, SENAO NAO FUNCIONA - VB
    //         unset($user->roles);

    //         $user->roles = $rolesArray;
            
    //         if( $hasDriver ) $user->driver_id = $userObj->driver->id;




    //         return $this->sendResponse($user, 'User retrieved successfully');
    //     }

    //     return $this->sendResponse([
    //         'error' => 'Unauthenticated user',
    //         'code' => 401,
    //     ], 'User not logged');

    // }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return
     */
    function register(Request $request)
    {
        $validation = $request->validate([
            'username' => 'required|string',
            'email' => 'required|string|email|unique:users',
//            'g-recaptcha-response' => (config('access.captcha.registration') ? ['required', new CaptchaRule()] : ''),
        ], [
//            'g-recaptcha-response.required' => __('validation.attributes.frontend.captcha'),
        ]);

        if (!$validation) {
            return response()->json(['errors' => $validation->errors()]);
        }
        // dd("");
        $user = new User([
            'first_name' => $request->first_name,
            // 'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        
        // $user->dob = isset($request->dob) ? $request->dob : null;
        // $user->phone = isset($request->phone) ? $request->phone : null;
        $user->username = isset($request->username) ? $request->username : null;
        $user->gender = isset($request->gender) ? $request->gender : null;
        $user->age = isset($request->age) ? $request->age : null;
        $user->height = isset($request->height) ? $request->height : null;
        $user->weight = isset($request->weight) ? $request->weight : null;
        $user->fitness_level = isset($request->fitness_level) ? $request->fitness_level : null;
        // $user->address = isset($request->address) ? $request->address : null;
        // $user->city = isset($request->city) ? $request->city : null;
        // $user->pincode = isset($request->pincode) ? $request->pincode : null;
        // $user->state = isset($request->state) ? $request->state : null;
        // $user->country = isset($request->country) ? $request->country : null;
        $user->save();

        // CREATE STRIPE CLIENT ACCOUNT

        // TODO - ADD ADDRESS INFORMATION
        // $address = [
        //     "city" => $request->city,
        //     "country" => $request->country,
        //     "line1" => $request->address,
        //     "line2" => null,
        //     "postal_code" => $request->postal_code,
        //     "state" => $request->state,
        // ];

        $user->createOrGetStripeCustomer();

        $user->updateStripeCustomer([
            'email' => $request->stripeEmail,
            // "address" => $address
        ]);



        $userForRole = User::find($user->id);
        $userForRole->confirmed = 1;
        $userForRole->save();
        $userForRole->assignRole('student');
        $user->save();


        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }
        $token->save();

        
        $user->api_token = $tokenResult->accessToken;
        $user->expires_at = Carbon::parse( $tokenResult->token->expires_at )->toDateTimeString();


        return response()->json([
            // 'status' => 'success',
            // 'message' => 'Successfully created user!',
            'data' => $user->toArray(),
            // 'access_token' => $tokenResult->accessToken,
            // 'token_type' => 'Bearer',
            // 'expires_at' => Carbon::parse(
            //     $tokenResult->token->expires_at
            // )->toDateTimeString()
        ], 201);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        
        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

        $token->save();
        
        // object to app info
        $user->api_token = $tokenResult->accessToken;
        $user->expires_at = Carbon::parse( $tokenResult->token->expires_at )->toDateTimeString();

        return response()->json([
            // 'access_token' => $tokenResult->accessToken,
            'data' => $user->toArray(),
            // 'token_type' => 'Bearer',
            // 'expires_at' => Carbon::parse(
            //     $tokenResult->token->expires_at
            // )->toDateTimeString()
        ]);
    }



    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        auth()->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * LOAD USER INFORMATION
     */
    public function loadUser(Request $request)
    {
        // dd(auth()->user()->id);
        // get user
        $user = User::where('id', '=', auth()->user()->id)->first();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

        $token->save();
        
        // object to app info
        $user->api_token = $tokenResult->accessToken;
        $user->expires_at = Carbon::parse( $tokenResult->token->expires_at )->toDateTimeString();
        
        return response()->json(['status' => 'success', 'result' => ["data" => $user->toArray()]]);
    }

    /**
     * Get Payment Method of Stripe User
     *
     * @return [string] message
     */
    public function userPaymentMethods(Request $request)
    {
        // get user
        $user = User::where('id', '=', auth()->user()->id)->first();

        // dd($user->stripe_id);


        $methods = $this->stripeWrapper->getAllPaymentMethod( $user->stripe_id );

        $cards = json_encode($methods->data);
        // dd(json_encode($methods->data));



        // $paymentMethod = $this->stripeWrapper->createPaymentMethod($card);

        // // dd($paymentMethod);
        
        // $paymentMethod = $this->stripeWrapper->attachPaymentMethod($paymentMethod->id, $user->stripe_id);

        // dd($paymentMethod);

        
        return response()->json(['status' => 'success', 'result' => ["data" => json_decode($cards)]]);
    }


    /**
     * Add Payment Method to Stripe User
     *
     * @return [string] message
     */
    public function addPaymentMethod(Request $request)
    {
        // get user
        $user = User::where('id', '=', auth()->user()->id)->first();

        $card = [
            'number' => $request->input('number'),
            'exp_month' => $request->input('exp_month'),
            'exp_year' => $request->input('exp_year'),
            'cvc' => $request->input('cvc'),
        ];

        // CREATE STRIPE PAYMENT METHOD
        $paymentMethod = $this->stripeWrapper->createPaymentMethod($card);
        Log::info("createPaymentMethod");
        Log::info($paymentMethod);
        // ATTACH PAYMENT TO CUSTOMER        
        $paymentMethod = $this->stripeWrapper->attachPaymentMethod($paymentMethod->id, $user->stripe_id);

        Log::info("attachPaymentMethod");
        Log::info($paymentMethod);

        // UPDATE METHOD TO ADD HOLDER NAME
        $updatedPayment = $this->stripeWrapper->updatePaymentMethod( $paymentMethod->id, $request->input('holder_name') );

        Log::info("updatePaymentMethod");
        Log::info($updatedPayment);
        
        $payment = json_encode($paymentMethod);

        
        return response()->json(['status' => 'success', 'result' => ["data" => json_decode($payment)]]);
    }

     /**
     * Add Payment Method to Stripe User
     *
     * @return [string] message
     */
    public function addSubscription(Request $request)
    {
        // get user
        $user = User::where('id', '=', auth()->user()->id)->first();

        // plan_K5OPh9NgjjmYds
        

        switch ($request->input('plan')) {
            case 'montly':
                $plan = "price_1LM7jIGw17JvxF7qxdI2ZjZY";
                break;
            case 'quarterly':
                $plan = "price_1JREHXGw17JvxF7q6Yzr31ez";
                break;
            case 'yearly':
                $plan = "price_1LXkOFGw17JvxF7qbPJ5b1XQ";
                break;
            default: $plan = "price_1LM7jIGw17JvxF7qxdI2ZjZY";
        }


        $payment = $request->input('payment');


        // UPDATE CUSTOMER DEFAULT PAYMENT METHOD WITH SELECTED OPTION
        $customer = $this->stripeWrapper->defaultPaymentMethod($user->stripe_id, $payment);
        
        // ADD SUBSCRIPTION TO CUSTOMER
        $addedSubscription = $this->stripeWrapper->addSubscriptionToCustomer($user->stripe_id, $plan);
        Log::info($addedSubscription);

        $subscription = json_encode($addedSubscription);

        // IF SUBSCRIPTION IS ACTIVE UPDATE USER
        if($addedSubscription->status == 'active'){
            $user->premium = '1';
            $user->save();
            
            return response()->json(['status' => 'success', 'result' => ["data" => json_decode($subscription)]]);

        }
        // TODO CHECK API ERROR CODES
        return response()->json(['status' => 'error'], 404);
    }


    public function usersSubscriptions(){

        $usersPremium = \App\Models\Auth\User::whereId(41)->whereNotNull('stripe_id')->where('premium', '=', 1)->get();


        foreach ($usersPremium as $user) {

            $subscription = $this->stripeWrapper->getAllSubscriptions($user->stripe_id);

            // dd($subscription);
            // if data not empty
            if( isset($subscription) && count($subscription->data[0]) > 0 ){
                if($subscription->data[0]->status != 'active'){
                    $user->premium = 0;
                    $user->save();
                }
            }else{
                $user->premium = 0;
                $user->save();
            }
        }

    }

    /**
     * SAVE INFORMATION RELATED TO APPLE SUBSCRIPTION PAYMENT TO FUTURE VALIDATION
     */
    public function appleSubscription(Request $request){

        $request->validate([
            'in_app_purchase_id' => 'required|string',
            'in_app_product_id' => 'required|string',
            'in_app_purchase_status' => 'required|string',
            'in_app_server_data' => 'required|string',
            'in_app_source' => 'required|string',
            'in_app_transaction_date' => 'required|string',
        ]);
        
        $input = $request->all();

        // GET USER MODEL
        $user = User::where('id', '=', auth()->user()->id)->first();
        
        $user->in_app_purchase_id = $input['in_app_purchase_id'];
        $user->in_app_product_id = $input['in_app_product_id'];
        $user->in_app_purchase_status = $input['in_app_purchase_status'];
        $user->in_app_server_data = $input['in_app_server_data'];
        $user->in_app_source = $input['in_app_source'];
        $user->in_app_transaction_date = $input['in_app_transaction_date'];


        // if success change user to premium access
        if($input['in_app_purchase_status'] == "purchased"){
            $user->premium = 1;
        }

        // SAVE CHANGES
        $user->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * 
     */
    public function deleteAccount(Request $request){

        // GET USER MODEL
        $user = User::where('id', '=', auth()->user()->id)->first();
        
        $user->email = "_".$user->email;

        // SAVE CHANGES
        $user->save();

        $user->delete();


        return response()->json(['status' => 'success']);

    }


    // function logout(Request $request)
    // {
    //     $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();
    //     if (!$user) {
    //         return $this->sendResponse([
    //             'error' => true,
    //             'code' => 404,
    //         ], 'User not found');
    //     }
    //     try {
    //         auth()->logout();
    //     } catch (ValidatorException $e) {
    //         return $this->sendResponse([
    //             'error' => true,
    //             'code' => 404,
    //         ], 'User not found');
    //     }
    //     return $this->sendResponse($user['name'], 'User logout successfully');

    // }

    // function user(Request $request)
    // {
    //     $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();

    //     if (!$user) {
    //         return $this->sendResponse([
    //             'error' => true,
    //             'code' => 404,
    //         ], 'User not found');
    //     }

    //     return $this->sendResponse($user, 'User retrieved successfully');
    // }

    // function settings(Request $request)
    // {
    //     $settings = setting()->all();
    //     //Log::warning($settings);

    //     $settings = array_intersect_key($settings,
    //         [
    //             'default_tax' => '',
    //             'default_currency' => '',
    //             'app_name' => '',
    //             'currency_right' => '',
    //             'enable_paypal' => '',
    //             'enable_stripe' => '',
    //             'main_color' => '',
    //             'main_dark_color' => '',
    //             'second_color' => '',
    //             'second_dark_color' => '',
    //             'accent_color' => '',
    //             'accent_dark_color' => '',
    //             'scaffold_dark_color' => '',
    //             'scaffold_color' => '',
    //             'google_maps_key' => '',
    //             'mobile_language' => '',
    //             'app_version' => '',
    //             'enable_version' => '',
    //             'distance_unit' => '',
    //             'distance_radius_fix' => '',
    //             'cart_minimum_value' => '',
    //             'module_cja' => '',
    //             'prep_tolerance' => '',
    //             // COLORS LIGHT THEME
    //             'color_white' => '',
    //             'color_black' => '',
    //             'color_bg' => '',
    //             'color_delete' => '',
    //             'color_open' => '',
    //             'color_second' => '',
    //             'color_blue_dark' => '',
    //             'color_text_disabled' => '',
    //             'color_disabled' => '',
    //             'color_primary' => '',
    //             'color_bg_card' => '',
    //             // COLORS DARK THEME
    //             'color_dark_white' => '',
    //             'color_dark_black' => '',
    //             'color_dark_bg' => '',
    //             'color_dark_delete' => '',
    //             'color_dark_open' => '',
    //             'color_dark_second' => '',
    //             'color_dark_blue_dark' => '',
    //             'color_dark_text_disabled' => '',
    //             'color_dark_disabled' => '',
    //             'color_dark_primary' => '',
    //             'color_dark_bg_card' => '',
                 
    //             'products_vat' => '',
    //             'products_vat_visibility' => '',
    //             'products_vat_description' => '',

    //             'delivery_fee_option' => '',
    //             'delivery_fee_base' => '',
    //             'delivery_fee_dist_min' => '',
    //             'delivery_fee_value' => '',
    //             //'delivery_fee_destination' => '',
                
    //             'service_fee_option' => '',
    //             'service_fee_base' => '',
    //             'service_fee_dist_min' => '',
    //             'service_fee_value' => '',
    //             //'service_fee_destination' => '',
				
	// 			//CUSTOMER APP VERSION
	// 			'app_customer_version_control' => '',
    //             'app_disabled' => ''
    //         ]
    //     );
	// 	/*
		
	// 	THIS IS TO VALIDATE BY DEVICE ID LIKE MULTIPOINT
		
    //     $deviceId = $request->input('device_id');
    //     if( !empty($deviceId) ) {
    //         $appDevice = Appdevice::where('device', $deviceId)->first();
    //         if( $appDevice ) {
    //             if( $appDevice->active != "1" )
    //                 $settings = null;
    //         }else {
    //             $appDevice = new Appdevice;
    //             $appDevice->device = $deviceId;
    //             $appDevice->description = "...new...";
    //             $appDevice->active = "0";
    //             $appDevice->save();
    //             $settings = null;
    //         }
    //     }else {
    //         $settings = null;
    //     }*/

    //     //DISABLE APP   
    //     if ($settings['app_disabled'] == 1) {
    //         return $this->sendResponse([
    //             'app_disabled' => true,
    //             'error' => true,
    //             'code' => 404,
    //         ], 'Settings not found');
    //     }
		
    //     if(config('app.hd_level') == "SQC"){
    //         // VALIDATE APP VERSION
    //         $appVersion = $request->input('app_version');
    //         if( !empty($appVersion) ) {
    //             //validate if app version is valid
    //             if($settings['app_customer_version_control'] != $appVersion){
    //                 $settings = null;
    //             }
                
    //         }else {
    //             $settings = null;
    //         }
    //     }
        
    //     if (!$settings) {
    //         return $this->sendResponse([
    //             'error' => true,
    //             'code' => 404,
    //         ], 'Settings not found');
    //     }

    //     return $this->sendResponse($settings, 'Settings retrieved successfully');
    // }

    // /**
    //  * Update the specified User in storage.
    //  *
    //  * @param int $id
    //  * @param Request $request
    //  *
    //  * @return Response
    //  */
    // public function update($id, Request $request)
    // {
    //     $user = $this->userRepository->findWithoutFail($id);

    //     if (empty($user)) {
    //         return $this->sendResponse([
    //             'error' => true,
    //             'code' => 404,
    //         ], 'User not found');
    //     }
    //     $input = $request->except(['password', 'api_token', 'cartaojovem_number', 'wallet']);
    //     try {
    //         if($request->has('device_token')){
    //             $user = $this->userRepository->update($request->only('device_token'), $id);
    //         }else{
    //             // $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
    //             // Check CartaoJovemAngola 
    //             if($request->has('cartaojovem_number'))
    //             {
    //                 if( $user->cartaojovem_number == NULL )
    //                 {
    //                     $cartaoJovem = $this->cartaoJovemRepository
    //                                         ->where(function($q) {
    //                                             $q->where('user_id', '0')
    //                                                 ->orWhere('user_id', Auth::id());
    //                                         })
    //                                         ->where('number', $request->get('cartaojovem_number'))
    //                                         ->first();
    //                     if($cartaoJovem)
    //                     {
    //                         $cartaoJovem->user_id = Auth::id();
    //                         $cartaoJovem->save();
    //                         $input['cartaojovem_number'] = $cartaoJovem->number;
    //                     }
    //                 }
    //             }
    //             // Update User Data
    //             $user = $this->userRepository->update($input, $id);

    //             // foreach (getCustomFieldsValues($customFields, $request) as $value) {
    //             //     $user->customFieldsValues()
    //             //         ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
    //             // }
    //         }
    //     } catch (ValidatorException $e) {
    //         return $this->sendResponse([
    //             'error' => true,
    //             'code' => 404,
    //         ], $e->getMessage());
    //     }

    //     return $this->sendResponse($user, __('lang.updated_successfully', ['operator' => __('lang.user')]));
    // }

    // function sendResetLinkEmail(Request $request)
    // {
	// 	/*$email = new DigitalMenuMail("w2w22ww2","info@yubane.co.ao");
	// 	$input = ['nome' => 'nome','contacto' => 'contacto','morada' => 'morada','cod-postal' => 'cod-postal','localidade' => 'localidade',
	// 			  'telemovel' => 'telemovel','horario' => 'horario','endr-email' => 'endr-email','pag-simples' => 'pag-simples',];
	// 	Mail::to('vascobatista16@gmail.com')->send($email->emailForPortal( $input, null ));*/
	// 	//dd($request);
    //     $this->validate($request, ['email' => 'required|email']);

    //     $response = Password::broker()->sendResetLink(
    //         $request->only('email')
    //     );
	// 	//dd($response);
    //     if ($response == Password::RESET_LINK_SENT) {
    //         return $this->sendResponse(true, 'Reset link was sent successfully');
    //     } else {
    //         return $this->sendError([
    //             'error' => 'Reset link not sent',
    //             'code' => 401,
    //         ], 'Reset link not sent');
    //     }

    // }
}
