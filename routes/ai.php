<?php

use App\Http\Controllers\OAuth\MetadataController;
use App\Http\Controllers\OAuth\RegistrationController;
use App\Http\Controllers\OAuth\TokenController;
use App\Mcp\Servers\AttendanceServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/attendance', AttendanceServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp-token']);

/*
|--------------------------------------------------------------------------
| OAuth 2.1 del conector MCP
|--------------------------------------------------------------------------
|
| Permite que Claude y ChatGPT se conecten con un inicio de sesión normal
| ("Iniciar sesión ahora"): descubren los endpoints, se registran solos,
| mandan al usuario a /oauth/authorize y canjean el código con PKCE.
|
| El token emitido es un token Sanctum con la habilidad mcp:read, así que
| auth:sanctum y el panel de tokens no cambian.
|
*/

Route::get('.well-known/oauth-protected-resource', [MetadataController::class, 'protectedResource'])
    ->name('oauth.protected-resource');

// laravel/mcp busca este nombre para anunciar resource_metadata en el 401 del MCP.
Route::get('.well-known/oauth-protected-resource/{path}', [MetadataController::class, 'protectedResource'])
    ->where('path', '.*')
    ->name('mcp.oauth.protected-resource.nested');

Route::get('.well-known/oauth-authorization-server', [MetadataController::class, 'authorizationServer'])
    ->name('oauth.authorization-server');

Route::get('.well-known/oauth-authorization-server/{path}', [MetadataController::class, 'authorizationServer'])
    ->where('path', '.*')
    ->name('oauth.authorization-server.nested');

Route::post('/oauth/register', RegistrationController::class)
    ->middleware('throttle:30,1')
    ->name('oauth.register');

Route::post('/oauth/token', TokenController::class)
    ->middleware('throttle:120,1')
    ->name('oauth.token');
