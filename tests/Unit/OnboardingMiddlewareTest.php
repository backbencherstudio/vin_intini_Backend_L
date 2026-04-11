<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\EnsureVerifiedUser;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class OnboardingMiddlewareTest extends TestCase
{
    public function test_verified_user_middleware_blocks_unverified_user(): void
    {
        $middleware = new EnsureVerifiedUser();

        $user = new User(['is_verified' => false]);
        $request = Request::create('/api/setup-profile', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('verify your email', (string) $response->getContent());
    }

    public function test_profile_completed_middleware_blocks_when_profile_missing(): void
    {
        $middleware = new EnsureProfileCompleted();

        $user = new User();
        $user->setRelation('profile', null);

        $request = Request::create('/api/groups', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Profile setup required', (string) $response->getContent());
    }

    public function test_profile_completed_middleware_allows_when_profile_exists(): void
    {
        $middleware = new EnsureProfileCompleted();

        $user = new User();
        $user->setRelation('profile', new UserProfile());

        $request = Request::create('/api/groups', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', (string) $response->getContent());
    }
}

