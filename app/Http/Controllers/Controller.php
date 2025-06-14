<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Crypto Bot API",
 *     description="API documentation for the Crypto Bot trading application",
 *     @OA\Contact(
 *         email="support@cryptobot.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local development server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
