<?php

namespace App\Services\Socialite;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Apple\Provider as AppleBaseProvider;

class AppleProvider extends AppleBaseProvider
{
    /**
     * Apple only accepts the client_secret as a POST form-field on the token
     * endpoint; it rejects the HTTP Basic Authorization header that the
     * upstream SocialiteProviders\Apple provider sends (invalid_client).
     */
    public function getAccessTokenResponse($code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->getClientSecret(),
                'code' => $code,
                'redirect_uri' => $this->redirectUrl,
            ],
        ]);

        return (array) json_decode((string) $response->getBody(), true);
    }
}
