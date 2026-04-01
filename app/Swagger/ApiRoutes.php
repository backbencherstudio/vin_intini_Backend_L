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
            new OA\Response(response: 403, description: 'Forbidden (email not verified)'),
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
        description: 'Registers a user and sends a 4-digit OTP to the email for verification.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6, example: 'secret123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Registration successful. OTP sent to your email.'),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(
                response: 500,
                description: 'Server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Registration failed.'),
                    ],
                ),
            ),
        ],
    )]
    public function postRegister(): void {}

    #[OA\Post(
        path: '/api/register/verify-otp',
        tags: ['Auth'],
        operationId: 'post_api_register_verify_otp',
        summary: 'AuthController@verifyRegisterOtp',
        description: 'Verifies the registration OTP and marks the user as verified.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'otp', type: 'string', description: '4-digit OTP', pattern: '^\\d{4}$', example: '1234'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email verified successfully.'),
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid or expired OTP',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid OTP'),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postRegisterVerifyOtp(): void {}

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
