<?php

namespace Goestijn\SocialiteEidProvider;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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

            $cacheKey = 'eid-idp.keys';
            $redirect = Config::get('services.eid.redirect');

            if (!Cache::has($cacheKey)) {

                $response = Http::throw()->withHeaders(['Content-Type' => 'application/json'])->post('https://www.e-contract.be/eid-idp/oidc/auth/register', [
                    'redirect_uris' => [$redirect]
                ])->json();

                Cache::put($cacheKey, [
                    'client_id' => $response['client_id'],
                    'client_secret' => $response['client_secret'],
                    'redirect' => $redirect,
                ], now()->parse($response['client_secret_expires_at']));
            }

            return $socialite->buildProvider(SocialiteProvider::class, Cache::get($cacheKey));
        });
    }
}
