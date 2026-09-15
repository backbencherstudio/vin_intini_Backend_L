<?php

namespace Tests\Unit;

use App\Services\Socialite\AppleProvider;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\InvalidStateException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use SocialiteProviders\Manager\Config;
use Tests\TestCase;

class AppleProviderTest extends TestCase
{
    private ?string $privateKey = null;

    private ?string $publicKeyPem = null;

    private const TEST_PRIVATE_KEY = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIGHAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBG0wawIBAQQgLRq8a+YlK3hwkJGq
Ycz7dqCGEyt8h+oSxG9IPrWdKfehRANCAAQ83nMExX9BL56IS7lZwhM2NkIrw4H5
j3E31i6Se0jMo1QNuzXaOiKObJjo9MEicRIerN51C4FO/X3u0h/qpzHl
-----END PRIVATE KEY-----
PEM;

    public function test_token_exchange_sends_client_secret_as_form_field_and_not_basic_auth(): void
    {
        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'at',
                'refresh_token' => 'rt',
                'expires_in' => 3600,
                'id_token' => 'it',
            ])),
        ]));
        $stack->push(Middleware::history($container));

        config([
            'services.apple.team_id' => '8G2A886H4H',
            'services.apple.client_id' => 'com.vinintini.mindunite.service',
            'services.apple.key_id' => 'Z58P5H5NBJ',
        ]);

        $provider = new AppleProvider(new Request, 'client-id', 'dummy-secret', 'https://vini.pixelstack.cloud/api/auth/apple/callback');
        $provider->setHttpClient(new Client(['handler' => $stack]));
        $provider->setConfig(new Config('client-id', 'dummy-secret', 'https://vini.pixelstack.cloud/api/auth/apple/callback', [
            'private_key' => self::TEST_PRIVATE_KEY,
        ]));

        $response = $provider->getAccessTokenResponse('auth-code');

        $this->assertSame('at', $response['access_token']);

        $request = $container[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://appleid.apple.com/auth/token', (string) $request->getUri());
        $this->assertFalse($request->hasHeader('Authorization'));

        parse_str((string) $request->getBody(), $fields);
        $this->assertSame('authorization_code', $fields['grant_type']);
        $this->assertSame('client-id', $fields['client_id']);
        $this->assertSame('auth-code', $fields['code']);
        $this->assertSame('https://vini.pixelstack.cloud/api/auth/apple/callback', $fields['redirect_uri']);
        $this->assertNotEmpty($fields['client_secret']);
        $this->assertSame(2, substr_count($fields['client_secret'], '.'));
    }

    public function test_check_token_accepts_service_id_audience(): void
    {
        $this->seedJwksCache();

        $provider = $this->createAppleProvider('com.vinintini.mindunite.service');
        $jwt = $this->buildAppleJwt('com.vinintini.mindunite.service');

        $this->assertTrue($provider->checkToken($jwt));
    }

    public function test_check_token_accepts_ios_bundle_id_audience(): void
    {
        $this->seedJwksCache();

        $provider = $this->createAppleProvider('com.vinintini.mindunite.service');
        $jwt = $this->buildAppleJwt('com.vinintini.mindunite');

        $this->assertTrue($provider->checkToken($jwt));
    }

    public function test_check_token_rejects_unknown_audience(): void
    {
        $this->seedJwksCache();

        $provider = $this->createAppleProvider('com.vinintini.mindunite.service');
        $jwt = $this->buildAppleJwt('com.unknown.app');

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The token is not allowed to be used by this audience.');

        $provider->checkToken($jwt);
    }

    private function createAppleProvider(string $clientId): AppleProvider
    {
        $provider = new AppleProvider(
            new Request,
            $clientId,
            'dummy-secret',
            'https://vini.pixelstack.cloud/api/auth/apple/callback'
        );

        $provider->setConfig(new Config(
            $clientId,
            'dummy-secret',
            'https://vini.pixelstack.cloud/api/auth/apple/callback',
        ));

        config([
            'services.apple.client_id_ios' => 'com.vinintini.mindunite',
        ]);

        return $provider;
    }

    private function seedJwksCache(): void
    {
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $privateKey);
        $details = openssl_pkey_get_details($res);

        $n = rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '=');
        $e = rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '=');

        Cache::put('socialite:Apple-JWKSet', [
            'keys' => [[
                'kty' => 'RSA',
                'kid' => 'test-rsa-key',
                'use' => 'sig',
                'alg' => 'RS256',
                'n' => $n,
                'e' => $e,
            ]],
        ], 300);

        $this->privateKey = $privateKey;
        $this->publicKeyPem = $details['key'];
    }

    private function buildAppleJwt(string $audience): string
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($this->privateKey),
            InMemory::plainText($this->publicKeyPem),
        );

        $now = new DateTimeImmutable;

        $token = $config->builder()
            ->issuedBy('https://appleid.apple.com')
            ->permittedFor($audience)
            ->relatedTo('user-sub-123')
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withHeader('kid', 'test-rsa-key')
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }
}
