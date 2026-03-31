<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

final class ApiRoutes
{
    #[OA\Get(
        path: '/api/login',
        tags: ['Auth'],
        operationId: 'get_api_login',
        summary: 'Login hint (401)',
        responses: [new OA\Response(response: 401, description: 'Unauthorized')],
    )]
    public function getLoginHint(): void {}

    #[OA\Post(
        path: '/api/login',
        tags: ['Auth'],
        operationId: 'post_api_login',
        summary: 'AuthController@login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postLogin(): void {}

    #[OA\Post(
        path: '/api/logout',
        tags: ['Auth'],
        operationId: 'post_api_logout',
        summary: 'AuthController@logout',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postLogout(): void {}

    #[OA\Get(
        path: '/api/me',
        tags: ['Auth'],
        operationId: 'get_api_me',
        summary: 'AuthController@me',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getMe(): void {}

    #[OA\Post(
        path: '/api/password-reset',
        tags: ['Password'],
        operationId: 'post_api_password_reset',
        summary: 'ForgotPasswordController@resetPassword',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postPasswordReset(): void {}

    #[OA\Put(
        path: '/api/profile-update',
        tags: ['Admin'],
        operationId: 'put_api_profile_update',
        summary: 'UserController@profileUpdate',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function putProfileUpdate(): void {}

    #[OA\Post(
        path: '/api/refresh',
        tags: ['Auth'],
        operationId: 'post_api_refresh',
        summary: 'AuthController@refresh',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postRefresh(): void {}

    #[OA\Post(
        path: '/api/register',
        tags: ['Auth'],
        operationId: 'post_api_register',
        summary: 'AuthController@register',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postRegister(): void {}

    #[OA\Post(
        path: '/api/send-otp',
        tags: ['Password'],
        operationId: 'post_api_send_otp',
        summary: 'ForgotPasswordController@sendOtp',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postSendOtp(): void {}


    #[OA\Post(
        path: '/api/verify-otp',
        tags: ['Password'],
        operationId: 'post_api_verify_otp',
        summary: 'ForgotPasswordController@verifyOtp',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postVerifyOtp(): void {}
}
