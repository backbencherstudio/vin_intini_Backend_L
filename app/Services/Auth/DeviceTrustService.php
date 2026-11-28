<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DeviceTrustService
{
    public const DEVICE_COOKIE_NAME = 'mu_device_id';

    public const DEVICE_HEADER_NAME = 'X-Device-ID';

    /**
     * Resolve an incoming device identifier (HttpOnly cookie for web, X-Device-ID header for mobile)
     * or mint a fresh cryptographic device ID for the response.
     *
     * @return array{plain: string, hash: string, is_new: bool}
     */
    public function resolveOrCreate(?Request $request = null): array
    {
        $req = $request ?? request();

        $existing = $this->extractPlainDeviceId($req);
        if ($existing !== null) {
            return [
                'plain' => $existing,
                'hash' => $this->hashDeviceId($existing),
                'is_new' => false,
            ];
        }

        $plain = bin2hex(random_bytes(32));

        return [
            'plain' => $plain,
            'hash' => $this->hashDeviceId($plain),
            'is_new' => true,
        ];
    }

    public function extractDeviceHash(?Request $request = null): ?string
    {
        $plain = $this->extractPlainDeviceId($request ?? request());

        return $plain !== null ? $this->hashDeviceId($plain) : null;
    }

    public function extractPlainDeviceId(Request $request): ?string
    {
        $candidates = [
            $request->attributes->get('resolved_device_id'),
            $request->cookie(self::DEVICE_COOKIE_NAME),
            $request->header(self::DEVICE_HEADER_NAME),
            $request->input('device_id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '' && strlen($candidate) <= 128) {
                return trim($candidate);
            }
        }

        return null;
    }

    public function hashDeviceId(string $plainDeviceId): string
    {
        return hash('sha256', $plainDeviceId.'|'.(string) config('app.key'));
    }

    public function attachDeviceCookie(Response $response, string $plainDeviceId, ?Request $request = null): Response
    {
        $req = $request ?? request();
        $secure = $req->isSecure() || app()->isProduction();

        $cookie = Cookie::create(
            name: self::DEVICE_COOKIE_NAME,
            value: $plainDeviceId,
            expire: now()->addDays(365),
            path: '/',
            domain: config('session.domain'),
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );

        $response->headers->setCookie($cookie);

        return $response;
    }
}
