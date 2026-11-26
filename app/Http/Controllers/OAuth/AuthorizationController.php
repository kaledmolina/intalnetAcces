<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\OAuthAuthorizationCode;
use App\Models\OAuthClient;
use App\Support\CimdClientResolver;
use App\Support\McpOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AuthorizationController extends Controller
{
    public function __construct(private readonly CimdClientResolver $cimd) {}

    /**
     * GET muestra el consentimiento; POST lo aprueba o rechaza.
     */
    public function __invoke(Request $request): View|Response|RedirectResponse
    {
        $client = $this->resolveClient($request);

        if (! $client) {
            return $this->error($this->cimd->isDocumentUrl((string) $request->input('client_id'))
                ? 'No se pudo leer la identidad pública de la aplicación (CIMD). Comprueba que el documento del cliente esté disponible y vuelve a iniciar la conexión.'
                : 'La aplicación no está registrada en este servidor. Vuelve a iniciar la conexión desde Claude o ChatGPT.');
        }

        $redirectUri = (string) $request->input('redirect_uri');

        if (! $this->redirectIsRegistered($client, $redirectUri)) {
            return $this->error('La dirección de retorno no pertenece a la aplicación registrada.');
        }

        if ($error = $this->validationError($request)) {
            return $this->redirectWith($redirectUri, [
                'error' => $error[0],
                'error_description' => $error[1],
                'state' => (string) $request->input('state'),
            ]);
        }

        $user = $request->user();

        if (! $user->is_active) {
            return $this->error('Tu cuenta está inactiva. Pide a un administrador que la active antes de conectar la IA.');
        }

        if (! $request->isMethod('post')) {
            return view('oauth.authorize', [
                'client' => $client,
                'user' => $user->loadMissing('sedeRelation'),
                'redirectUri' => $redirectUri,
                'state' => (string) $request->input('state'),
                'codeChallenge' => (string) $request->input('code_challenge'),
                'destination' => McpOAuth::displayDestination($redirectUri),
                'clientIdHost' => $this->cimd->isDocumentUrl($client->client_id)
                    ? McpOAuth::displayDestination($client->client_id)
                    : null,
            ]);
        }

        if ((string) $request->input('decision') !== 'approve') {
            return $this->redirectWith($redirectUri, [
                'error' => 'access_denied',
                'error_description' => 'El usuario rechazó el acceso.',
                'state' => (string) $request->input('state'),
            ]);
        }

        $code = bin2hex(random_bytes(32));

        OAuthAuthorizationCode::create([
            'oauth_client_id' => $client->id,
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'redirect_uri' => $redirectUri,
            'code_challenge' => (string) $request->input('code_challenge'),
            'expires_at' => now()->addMinutes(McpOAuth::AUTH_CODE_MINUTES),
        ]);

        return $this->redirectWith($redirectUri, [
            'code' => $code,
            'state' => (string) $request->input('state'),
        ]);
    }

    /**
     * Cliente ya registrado (DCR) o descrito por un documento CIMD.
     */
    private function resolveClient(Request $request): ?OAuthClient
    {
        $clientId = (string) $request->input('client_id');

        if ($clientId === '') {
            return null;
        }

        return OAuthClient::where('client_id', $clientId)->first()
            ?? $this->cimd->resolve($clientId);
    }

    private function redirectIsRegistered(OAuthClient $client, string $redirectUri): bool
    {
        if ($redirectUri === '') {
            return false;
        }

        /** @var array<int, mixed> $registered */
        $registered = $client->redirect_uris ?? [];

        if (in_array($redirectUri, $registered, true)) {
            return true;
        }

        // RFC 8252: en loopback se ignora el puerto efímero que elige la app
        // (Claude Code publica http://localhost/callback sin puerto).
        return $this->matchesLoopbackRedirect($redirectUri, $registered);
    }

    /**
     * @param  array<int, mixed>  $registered
     */
    private function matchesLoopbackRedirect(string $redirectUri, array $registered): bool
    {
        $requested = parse_url($redirectUri);

        if ($requested === false || strtolower((string) ($requested['scheme'] ?? '')) !== 'http') {
            return false;
        }

        $host = strtolower((string) ($requested['host'] ?? ''));

        if (! in_array($host, ['localhost', '127.0.0.1', '[::1]'], true) || ! isset($requested['port'])) {
            return false;
        }

        foreach ($registered as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $parts = parse_url($candidate);

            if ($parts === false || strtolower((string) ($parts['scheme'] ?? '')) !== 'http') {
                continue;
            }

            if (strtolower((string) ($parts['host'] ?? '')) !== $host || isset($parts['port'])) {
                continue;
            }

            if (($parts['path'] ?? '') !== ($requested['path'] ?? '') || ($parts['query'] ?? '') !== ($requested['query'] ?? '')) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function validationError(Request $request): ?array
    {
        if ((string) $request->input('response_type') !== 'code') {
            return ['unsupported_response_type', 'Solo se admite response_type=code.'];
        }

        $challenge = (string) $request->input('code_challenge');

        if (preg_match('/^[A-Za-z0-9\-._~]{43,128}$/', $challenge) !== 1) {
            return ['invalid_request', 'Falta un code_challenge PKCE válido.'];
        }

        if (strtoupper((string) $request->input('code_challenge_method', 'S256')) !== 'S256') {
            return ['invalid_request', 'Solo se admite PKCE con S256.'];
        }

        $scope = trim((string) $request->input('scope'));

        foreach (preg_split('/\s+/', $scope !== '' ? $scope : McpOAuth::SCOPE) ?: [] as $requested) {
            // offline_access es inocuo: el refresh token se emite igual.
            if (! in_array($requested, [McpOAuth::SCOPE, 'offline_access'], true)) {
                return ['invalid_scope', 'El permiso solicitado no existe.'];
            }
        }

        $resource = (string) $request->input('resource');

        if ($resource !== '' && rtrim($resource, '/') !== rtrim(McpOAuth::resourceUrl(), '/')) {
            return ['invalid_target', 'El recurso solicitado no es este servidor MCP.'];
        }

        return null;
    }

    /**
     * @param  array<string, string>  $params
     */
    private function redirectWith(string $redirectUri, array $params): RedirectResponse
    {
        $params = array_filter($params, fn (string $value): bool => $value !== '');
        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return redirect()->away($redirectUri.$separator.http_build_query($params));
    }

    private function error(string $message): Response
    {
        return response()->view('oauth.error', ['message' => $message], 400);
    }
}
