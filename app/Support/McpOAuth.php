<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Piezas compartidas del servidor OAuth 2.1 que protege el conector MCP.
 *
 * El token que se emite es un token personal de Sanctum con la habilidad
 * `mcp:read`, así que el guard `auth:sanctum` y el panel de tokens siguen
 * funcionando igual y el tenant/sede se derivan del usuario autenticado.
 */
class McpOAuth
{
    /** Permiso OAuth que piden los clientes MCP. */
    public const SCOPE = 'mcp:use';

    /** Habilidad real del token Sanctum emitido. */
    public const ABILITY = 'mcp:read';

    public const AUTH_CODE_MINUTES = 10;

    public const ACCESS_TOKEN_MINUTES = 720;

    public const REFRESH_TOKEN_DAYS = 30;

    /** Esquemas de retorno de apps nativas (RFC 8252), además de https. */
    private const CUSTOM_SCHEMES = ['claude', 'cursor', 'vscode', 'chatgpt'];

    public static function resourceUrl(): string
    {
        return url('/mcp/attendance');
    }

    public static function issuer(): string
    {
        return rtrim(url('/'), '/');
    }

    /**
     * Respuesta JSON de OAuth: nunca se cachea.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function json(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->withHeaders([
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Verifica PKCE S256 (obligatorio para clientes públicos).
     */
    public static function verifyPkce(string $verifier, string $challenge): bool
    {
        if (strlen($verifier) < 43 || strlen($verifier) > 128) {
            return false;
        }

        if (preg_match('/^[A-Za-z0-9\-._~]+$/', $verifier) !== 1) {
            return false;
        }

        $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return hash_equals($challenge, $computed);
    }

    /**
     * Valida una redirect_uri de un cliente OAuth (RFC 8252 y RFC 7591).
     */
    public static function isValidRedirectUri(string $uri): bool
    {
        if ($uri === '' || strlen($uri) > 1024) {
            return false;
        }

        $parts = parse_url($uri);

        if ($parts === false || empty($parts['scheme'])) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return false;
        }

        $scheme = strtolower((string) $parts['scheme']);

        if (in_array($scheme, ['http', 'https'], true)) {
            $host = strtolower((string) ($parts['host'] ?? ''));

            if ($host === '') {
                return false;
            }

            // http solo se admite para clientes locales.
            return $scheme === 'https' || in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
        }

        return in_array($scheme, self::CUSTOM_SCHEMES, true) && ! empty($parts['host']);
    }

    /**
     * Texto corto para que el usuario vea a dónde vuelve tras autorizar.
     */
    public static function displayDestination(string $redirectUri): string
    {
        $host = parse_url($redirectUri, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $redirectUri;
    }
}
