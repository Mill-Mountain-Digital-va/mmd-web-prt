<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Payments\Stripe\StripeWrapper;

class UserSubscriptionsValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will validate on stripe if user with subscription continues active';


    private $stripeWrapper;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(StripeWrapper $stripeWrapper)
    {
        $this->stripeWrapper = $stripeWrapper;
        parent::__construct();
        
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $usersPremium = \App\Models\Auth\User::whereNotNull('stripe_id')->where('premium', '=', 1)->get();

        foreach ($usersPremium as $user) {

            $subscription = $this->stripeWrapper->getAllSubscriptions($user->stripe_id);

            // if data not empty
            if( isset($subscription) && count($subscription->data[0]) > 0 ){
                // if subscription is available and status is active
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
}
