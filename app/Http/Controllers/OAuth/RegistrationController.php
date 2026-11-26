<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\OAuthClient;
use App\Support\McpOAuth;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    /**
     * Registro dinámico de clientes (RFC 7591), el respaldo cuando CIMD no
     * aplica. Son clientes públicos: sin secret y con PKCE obligatorio.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_name' => ['nullable', 'string', 'min:1', 'max:255'],
            'redirect_uris' => ['required', 'array', 'min:1', 'max:10'],
            'redirect_uris.*' => ['required', 'string', 'max:1024', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! McpOAuth::isValidRedirectUri($value)) {
                    $fail('Cada redirect_uri debe ser una URL absoluta válida (https, http en localhost o un esquema de app permitido).');
                }
            }],
            'token_endpoint_auth_method' => ['nullable', 'string', 'in:none'],
        ]);

        if ($validator->fails()) {
            $isRedirectError = (string) ($validator->errors()->first('redirect_uris') ?: $validator->errors()->first('redirect_uris.*')) !== '';

            return McpOAuth::json([
                'error' => $isRedirectError ? 'invalid_redirect_uri' : 'invalid_client_metadata',
                'error_description' => (string) $validator->errors()->first(),
            ], 400);
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        $redirectUris = array_values(array_map('strval', (array) $validated['redirect_uris']));
        $name = trim((string) ($validated['client_name'] ?? ''));
        $name = $name !== '' ? $name : (McpOAuth::displayDestination($redirectUris[0]) ?: 'Cliente MCP');

        $client = OAuthClient::create([
            'client_id' => 'mcp_'.Str::random(32),
            'name' => Str::limit($name, 120, ''),
            'redirect_uris' => $redirectUris,
        ]);

        return McpOAuth::json([
            'client_id' => $client->client_id,
            'client_id_issued_at' => $client->created_at?->getTimestamp(),
            'client_name' => $client->name,
            'redirect_uris' => $client->redirect_uris,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
            'scope' => McpOAuth::SCOPE,
        ], 201);
    }
}
