<?php

namespace Goestijn\SocialiteProviderEid;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class SocialiteProvider extends AbstractProvider
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'profile', 'address', 'photo', 'cert'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the authentication URL for the provider.
     *
     * @param  string  $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.e-contract.be/eid-idp/oidc/auth/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://www.e-contract.be/eid-idp/oidc/auth/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        return Http::throw()->withToken($token)->get('https://www.e-contract.be/eid-idp/oidc/auth/userinfo')->json();
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array  $user
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'name' => $user['name'],
            'given_name' => $user['given_name'],
            'middle_name' => $user['middle_name'],
            'family_name' => $user['family_name'],
            'avatar' => $user['photo'],
            'nationality' => $user['beid_nationality'],
            'birthdate' => \Carbon\Carbon::parse($user['birthdate']),
            'place_of_birth' => $user['place_of_birth']['locality'],
            'gender' => $user['gender'],
            'address' => [
                'street_address' => $user['address']['street_address'],
                'zip' => $user['address']['postal_code'],
                'locality' => $user['address']['locality'],
            ],
            'card' => [
                'number' => $user['beid_card_number'],
                'chip' => $user['beid_chip_number'],
                'delivery_municipality' => $user['beid_card_delivery_municipality'],
                'validity_begin' => \Carbon\Carbon::parse($user['beid_card_validity_begin']),
                'validity_end' => \Carbon\Carbon::parse($user['beid_card_validity_end']),
            ]
        ]);
    }

    /**
     * Obtain client id and client secret
     *
     * @param  string $redirect
     * @return array
     */
    public static function config(string $redirect): array
    {
        if ($config = Cache::get('eid-idp.keys'))
            return $config;


        $config = Http::throw()->withHeaders(['Content-Type' => 'application/json'])->post('https://www.e-contract.be/eid-idp/oidc/auth/register', [
            'redirect_uri' => [$redirect]
        ])->json();

        Cache::add('eid-idp.keys', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect' => $redirect,
        ], now()->parse($config['client_secret_expires_at']));

        return self::config($redirect);
    }
}
