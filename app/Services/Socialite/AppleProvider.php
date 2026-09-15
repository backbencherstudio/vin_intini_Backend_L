<?php

namespace App\Services\Socialite;

use DateInterval;
use Firebase\JWT\JWK;
use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\InvalidStateException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use SocialiteProviders\Apple\AppleSignerInMemory;
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

    /**
     * Override checkToken to accept both the iOS Bundle ID and the
     * Android/Web Service ID as valid audiences.
     *
     * Apple sends aud=Bundle ID on iOS and aud=Service ID on Android/Web.
     */
    public function checkToken($jwt, $nonce = null)
    {
        $token = $this->getJwtConfig()->parser()->parse($jwt);

        $publicKeys = JWK::parseKeySet($this->getJwkSet());
        $kid = $token->headers()->get('kid');

        if (! isset($publicKeys[$kid])) {
            throw new InvalidStateException('Invalid JWT Signature');
        }

        $publicKey = openssl_pkey_get_details($publicKeys[$kid]->getKeyMaterial());

        // Allow both the iOS Bundle ID and the Android/Web Service ID as valid audiences.
        $allowedAudiences = array_filter([
            $this->clientId,
            (string) config('services.apple.client_id_ios'),
        ]);
        $permitted = false;
        foreach ($allowedAudiences as $audience) {
            if ($token->isPermittedFor($audience)) {
                $permitted = true;
                break;
            }
        }

        if (! $permitted) {
            throw new InvalidStateException('The token is not allowed to be used by this audience.');
        }

        $constraints = [
            new SignedWith(new Sha256, AppleSignerInMemory::plainText($publicKey['key'])),
            new IssuedBy(self::URL),
            new LooseValidAt(SystemClock::fromSystemTimezone(), new DateInterval($this->getConfig('jwt_issued_time_leeway', 'PT3S'))),
        ];

        if ($nonce !== null) {
            $constraints[] = new HasClaimWithValue('nonce', $nonce);
        }

        try {
            $this->jwtConfig->validator()->assert($token, ...$constraints);

            return true;
        } catch (RequiredConstraintsViolated $e) {
            throw new InvalidStateException($e->getMessage());
        }
    }
}
