<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Support\McpOAuth;
use Illuminate\Http\JsonResponse;

class MetadataController extends Controller
{
    /**
     * RFC 9728: metadatos del recurso protegido (el servidor MCP).
     */
    public function protectedResource(): JsonResponse
    {
        return McpOAuth::json([
            'resource' => McpOAuth::resourceUrl(),
            'authorization_servers' => [McpOAuth::issuer()],
            'scopes_supported' => [McpOAuth::SCOPE],
            'bearer_methods_supported' => ['header'],
        ]);
    }

    /**
     * RFC 8414: metadatos del servidor de autorización.
     */
    public function authorizationServer(): JsonResponse
    {
        return McpOAuth::json([
            'issuer' => McpOAuth::issuer(),
            'authorization_endpoint' => route('oauth.authorize'),
            'token_endpoint' => route('oauth.token'),
            'registration_endpoint' => route('oauth.register'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => [McpOAuth::SCOPE],
            // CIMD (client_id = URL del documento) con DCR como respaldo.
            'client_id_metadata_document_supported' => true,
        ]);
    }
}
