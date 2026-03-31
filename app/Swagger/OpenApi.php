<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'API Documentation')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
final class OpenApi
{
}
