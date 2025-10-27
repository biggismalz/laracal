<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($secret = config('services.stripe.secret')) {
            $this->app->singleton(\Stripe\StripeClient::class, function () use ($secret) {
                return new \Stripe\StripeClient($secret);
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
