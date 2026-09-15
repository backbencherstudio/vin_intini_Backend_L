<?php

namespace Tests\Unit;

use App\Services\Socialite\AppleProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use SocialiteProviders\Manager\Config;
use Tests\TestCase;

class AppleProviderTest extends TestCase
{
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
}
