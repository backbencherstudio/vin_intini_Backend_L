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
        description: 'Registers a user and sends a 4-digit OTP to the email for verification. If the email already exists but is not verified, it will resend the OTP.',
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
        path: '/api/register/resend-otp',
        tags: ['Auth'],
        operationId: 'post_api_register_resend_otp',
        summary: 'AuthController@resendRegisterOtp',
        description: 'Resends the registration OTP (use when OTP expired).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ],
    )]
    public function postRegisterResendOtp(): void {}

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

    #[OA\Get(
        path: '/api/auth/{provider}',
        tags: ['Auth'],
        operationId: 'get_api_auth_provider',
        summary: 'SocialController@redirect',
        parameters: [
            new OA\Parameter(name: 'provider', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'google')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 302, description: 'Redirect'),
        ],
    )]
    public function getSocialRedirect(): void {}

    #[OA\Get(
        path: '/api/auth/{provider}/callback',
        tags: ['Auth'],
        operationId: 'get_api_auth_provider_callback',
        summary: 'SocialController@callback',
        parameters: [
            new OA\Parameter(name: 'provider', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'google')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function getSocialCallback(): void {}

    #[OA\Post(
        path: '/api/setup-profile',
        tags: ['Profile'],
        operationId: 'post_api_setup_profile',
        summary: 'UserProfileController@setupProfile',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postSetupProfile(): void {}

    #[OA\Post(
        path: '/api/update-password',
        tags: ['Admin'],
        operationId: 'post_api_update_password',
        summary: 'UserController@updatePass',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['old_password', 'new_password'],
                properties: [
                    new OA\Property(property: 'old_password', type: 'string', format: 'password'),
                    new OA\Property(property: 'new_password', type: 'string', format: 'password', minLength: 6),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postUpdatePassword(): void {}

    #[OA\Get(
        path: '/api/groups',
        tags: ['Groups'],
        operationId: 'get_api_groups',
        summary: 'GroupController@index',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getGroups(): void {}

    #[OA\Post(
        path: '/api/group-create',
        tags: ['Groups'],
        operationId: 'post_api_group_create',
        summary: 'GroupController@store',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object'),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postGroupCreate(): void {}

    #[OA\Get(
        path: '/api/group-show/{id}',
        tags: ['Groups'],
        operationId: 'get_api_group_show',
        summary: 'GroupController@show',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getGroupShow(): void {}

    #[OA\Post(
        path: '/api/group-update/{id}',
        tags: ['Groups'],
        operationId: 'post_api_group_update',
        summary: 'GroupController@update',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postGroupUpdate(): void {}

    #[OA\Get(
        path: '/api/my-created-groups',
        tags: ['Groups'],
        operationId: 'get_api_my_created_groups',
        summary: 'GroupController@myCreatedGroups',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getMyCreatedGroups(): void {}

    #[OA\Get(
        path: '/api/my-joined-groups',
        tags: ['Groups'],
        operationId: 'get_api_my_joined_groups',
        summary: 'GroupController@myJoinedGroups',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getMyJoinedGroups(): void {}

    #[OA\Post(
        path: '/api/group/join',
        tags: ['Groups'],
        operationId: 'post_api_group_join',
        summary: 'GroupController@joinGroup',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['group_id'],
                properties: [new OA\Property(property: 'group_id', type: 'integer', example: 1)],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postGroupJoin(): void {}

    #[OA\Post(
        path: '/api/group/leave',
        tags: ['Groups'],
        operationId: 'post_api_group_leave',
        summary: 'GroupController@leaveGroup',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['group_id'],
                properties: [new OA\Property(property: 'group_id', type: 'integer', example: 1)],
            ),
        ),
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postGroupLeave(): void {}

    #[OA\Post(
        path: '/api/group-invitations/{invitationId}/accept',
        tags: ['Groups'],
        operationId: 'post_api_group_invitation_accept',
        summary: 'GroupController@acceptInvitation',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'invitationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postGroupInvitationAccept(): void {}

    #[OA\Post(
        path: '/api/group-invitations/{invitationId}/ignore',
        tags: ['Groups'],
        operationId: 'post_api_group_invitation_ignore',
        summary: 'GroupController@ignoreInvitation',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'invitationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postGroupInvitationIgnore(): void {}

    #[OA\Get(
        path: '/api/company-suggestions',
        tags: ['Suggestions'],
        operationId: 'get_api_company_suggestions',
        summary: 'UserExperienceController@companySuggestions',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getCompanySuggestions(): void {}

    #[OA\Get(
        path: '/api/skill-suggestions',
        tags: ['Suggestions'],
        operationId: 'get_api_skill_suggestions',
        summary: 'UserExperienceController@skillSuggestions',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getSkillSuggestions(): void {}

    #[OA\Get(
        path: '/api/institution-suggestions',
        tags: ['Suggestions'],
        operationId: 'get_api_institution_suggestions',
        summary: 'UserEducationController@institutionSuggestions',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getInstitutionSuggestions(): void {}

    #[OA\Get(
        path: '/api/profile',
        tags: ['Profile'],
        operationId: 'get_api_profile',
        summary: 'UserProfileController@show',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getProfile(): void {}

    #[OA\Put(
        path: '/api/profile/update',
        tags: ['Profile'],
        operationId: 'put_api_profile_update_self',
        summary: 'UserProfileController@update',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function putProfileUpdateSelf(): void {}

    #[OA\Post(
        path: '/api/profile/images',
        tags: ['Profile'],
        operationId: 'post_api_profile_images',
        summary: 'UserProfileController@updateImages',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(type: 'object'),
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postProfileImages(): void {}

    #[OA\Get(
        path: '/api/connections',
        tags: ['Connections'],
        operationId: 'get_api_connections',
        summary: 'ConnectionController@index',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getConnections(): void {}

    #[OA\Get(
        path: '/api/connections/suggestions',
        tags: ['Connections'],
        operationId: 'get_api_connections_suggestions',
        summary: 'ConnectionController@suggestions',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'vin')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 12)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getConnectionSuggestions(): void {}

    #[OA\Get(
        path: '/api/connections/requests',
        tags: ['Connections'],
        operationId: 'get_api_connections_requests',
        summary: 'ConnectionController@requests',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getConnectionRequests(): void {}

    #[OA\Delete(
        path: '/api/connections/{user}/remove',
        tags: ['Connections'],
        operationId: 'delete_api_connections_user_remove',
        summary: 'ConnectionController@removeConnection',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 2))],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function deleteConnectionRemove(): void {}

    #[OA\Post(
        path: '/api/connections/{user}/follow',
        tags: ['Connections'],
        operationId: 'post_api_connections_user_follow',
        summary: 'FollowController@follow',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 2))],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postConnectionFollow(): void {}

    #[OA\Post(
        path: '/api/connections/request',
        tags: ['Connections'],
        operationId: 'post_api_connections_request',
        summary: 'ConnectionController@sendRequest',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [new OA\Property(property: 'user_id', type: 'integer', example: 2)],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postConnectionRequest(): void {}

    #[OA\Post(
        path: '/api/connections/requests/{connectionRequest}/accept',
        tags: ['Connections'],
        operationId: 'post_api_connections_requests_accept',
        summary: 'ConnectionController@accept',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'connectionRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postConnectionRequestAccept(): void {}

    #[OA\Post(
        path: '/api/connections/requests/{connectionRequest}/ignore',
        tags: ['Connections'],
        operationId: 'post_api_connections_requests_ignore',
        summary: 'ConnectionController@ignore',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'connectionRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function postConnectionRequestIgnore(): void {}

    #[OA\Get(
        path: '/api/connections/followers',
        tags: ['Connections'],
        operationId: 'get_api_connections_followers',
        summary: 'FollowController@followers',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getConnectionFollowers(): void {}

    #[OA\Get(
        path: '/api/connections/following',
        tags: ['Connections'],
        operationId: 'get_api_connections_following',
        summary: 'FollowController@following',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getConnectionFollowing(): void {}

    #[OA\Delete(
        path: '/api/connections/{user}/unfollow',
        tags: ['Connections'],
        operationId: 'delete_api_connections_user_unfollow',
        summary: 'FollowController@unfollow',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 2))],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function deleteConnectionUnfollow(): void {}

    #[OA\Get(
        path: '/api/experience/list',
        tags: ['Experience'],
        operationId: 'get_api_experience_list',
        summary: 'UserExperienceController@index',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getExperienceList(): void {}

    #[OA\Post(
        path: '/api/experience/add',
        tags: ['Experience'],
        operationId: 'post_api_experience_add',
        summary: 'UserExperienceController@store',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postExperienceAdd(): void {}

    #[OA\Get(
        path: '/api/experience/edit/{id}',
        tags: ['Experience'],
        operationId: 'get_api_experience_edit',
        summary: 'UserExperienceController@edit',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getExperienceEdit(): void {}

    #[OA\Post(
        path: '/api/experience/update/{id}',
        tags: ['Experience'],
        operationId: 'post_api_experience_update',
        summary: 'UserExperienceController@update',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postExperienceUpdate(): void {}

    #[OA\Delete(
        path: '/api/experience/delete/{id}',
        tags: ['Experience'],
        operationId: 'delete_api_experience_delete',
        summary: 'UserExperienceController@destroy',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function deleteExperience(): void {}

    #[OA\Get(
        path: '/api/education/list',
        tags: ['Education'],
        operationId: 'get_api_education_list',
        summary: 'UserEducationController@index',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getEducationList(): void {}

    #[OA\Post(
        path: '/api/education/add',
        tags: ['Education'],
        operationId: 'post_api_education_add',
        summary: 'UserEducationController@store',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postEducationAdd(): void {}

    #[OA\Get(
        path: '/api/education/edit/{id}',
        tags: ['Education'],
        operationId: 'get_api_education_edit',
        summary: 'UserEducationController@edit',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function getEducationEdit(): void {}

    #[OA\Post(
        path: '/api/education/update/{id}',
        tags: ['Education'],
        operationId: 'post_api_education_update',
        summary: 'UserEducationController@update',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function postEducationUpdate(): void {}

    #[OA\Delete(
        path: '/api/education/delete/{id}',
        tags: ['Education'],
        operationId: 'delete_api_education_delete',
        summary: 'UserEducationController@destroy',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function deleteEducation(): void {}
}
