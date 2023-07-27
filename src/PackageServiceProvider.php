<?php

namespace Goestijn\SocialiteProviderEid;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $socialite = $this->app->make(Factory::class);

        $socialite->extend('eid', function() use ($socialite) {
            return $socialite->buildProvider(SocialiteProvider::class, SocialiteProvider::config(Config::get('services.eid.redirect')));
        });
    }
}
