<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Surveillance API Documentation',
    description: 'L5 Swagger OpenApi description',
    contact: new OA\Contact(email: 'admin@admin.com'),
    license: new OA\License(name: 'Apache 2.0', url: 'http://www.apache.org/licenses/LICENSE-2.0.html')
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Demo API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'apikey',
    type: 'apiKey',
    in: 'header',
    name: 'x-api-key'
)]
abstract class Controller
{
    //
}
