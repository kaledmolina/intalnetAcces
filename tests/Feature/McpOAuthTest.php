<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    private const REDIRECT = 'https://claude.ai/api/mcp/auth_callback';

    public function test_publica_los_metadatos_oauth(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp/attendance'))
            ->assertJsonPath('authorization_servers.0', url('/'))
            ->assertJsonPath('scopes_supported.0', 'mcp:use');

        $this->getJson('/.well-known/oauth-protected-resource/mcp/attendance')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp/attendance'));

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
            ->assertJsonPath('token_endpoint', url('/oauth/token'))
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256')
            ->assertJsonPath('token_endpoint_auth_methods_supported.0', 'none');
    }

    public function test_el_mcp_sin_token_anuncia_donde_autenticarse(): void
    {
        $response = $this->postJson('/mcp/attendance', []);

        $response->assertUnauthorized();

        $this->assertStringContainsString(
            '/.well-known/oauth-protected-resource/mcp/attendance',
            (string) $response->headers->get('WWW-Authenticate'),
        );
    }

    public function test_el_registro_dinamico_acepta_clientes_publicos(): void
    {
        $client = $this->registerClient();

        $this->assertNotEmpty($client['client_id']);
        $this->assertSame(self::REDIRECT, $client['redirect_uris'][0]);
        $this->assertSame('none', $client['token_endpoint_auth_method']);
        $this->assertSame('mcp:use', $client['scope']);
        $this->assertSame(['code'], $client['response_types']);
    }

    public function test_el_registro_dinamico_rechaza_redirects_inseguros(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'Sitio ajeno',
            'redirect_uris' => ['http://evil.example/callback'],
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_redirect_uri');
    }

    public function test_autorizar_exige_iniciar_sesion(): void
    {
        $client = $this->registerClient();

        $this->get($this->authorizeUrl($client))
            ->assertRedirect(route('login'));
    }

    public function test_la_pantalla_de_consentimiento_muestra_la_aplicacion_y_la_sede(): void
    {
        $user = $this->user();
        $client = $this->registerClient();

        $this->actingAs($user)
            ->get($this->authorizeUrl($client))
            ->assertOk()
            ->assertSee('Claude')
            ->assertSee('Autorizar acceso')
            ->assertSee('encargado@example.com')
            ->assertSee('claude.ai');
    }

    public function test_el_flujo_completo_emite_un_token_de_solo_lectura_utilizable(): void
    {
        $user = $this->user();

        [$client, $tokens] = $this->tokensFor($user);

        $this->assertSame('Bearer', $tokens['token_type']);
        $this->assertSame('mcp:use', $tokens['scope']);
        $this->assertNotEmpty($tokens['refresh_token']);
        $this->assertGreaterThan(0, $tokens['expires_in']);

        $token = PersonalAccessToken::findToken($tokens['access_token']);

        $this->assertNotNull($token);
        $this->assertSame($user->id, (int) $token->tokenable_id);
        $this->assertContains('mcp:read', $token->abilities);
        $this->assertSame('Claude (OAuth)', $token->name);
        $this->assertNotNull($token->expires_at);

        $this->postJson('/mcp/attendance', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'catalogo_sedes_departamentos',
                'arguments' => [],
            ],
        ], [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ])
            ->assertOk()
            ->assertJsonPath('result.structuredContent.herramienta', 'catalogo_sedes_departamentos');
    }

    public function test_el_codigo_no_se_puede_canjear_dos_veces(): void
    {
        $user = $this->user();
        $client = $this->registerClient();
        $pkce = $this->pkce();
        $code = $this->codeFor($user, $client, $pkce);

        $this->exchange($client, $code, $pkce[0])->assertOk();

        $this->exchange($client, $code, $pkce[0])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_rechaza_un_code_verifier_que_no_corresponde(): void
    {
        $user = $this->user();
        $client = $this->registerClient();
        $pkce = $this->pkce();
        $code = $this->codeFor($user, $client, $pkce);

        $this->exchange($client, $code, Str::random(64))
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_rechaza_una_redirect_uri_distinta_al_canjear(): void
    {
        $user = $this->user();
        $client = $this->registerClient();
        $pkce = $this->pkce();
        $code = $this->codeFor($user, $client, $pkce);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client['client_id'],
            'code' => $code,
            'redirect_uri' => 'https://claude.ai/otra/ruta',
            'code_verifier' => $pkce[0],
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_el_refresh_rota_el_token_y_revoca_el_anterior(): void
    {
        $user = $this->user();

        [$client, $tokens] = $this->tokensFor($user);

        $refresh = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client['client_id'],
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk();

        $newAccess = (string) $refresh->json('access_token');

        $this->assertNotSame($tokens['access_token'], $newAccess);

        // Cerrar la sesión web para comprobar el token y no la cookie del usuario.
        $this->post('/logout');

        $this->assertNull(PersonalAccessToken::findToken($tokens['access_token']));

        $this->postJson('/mcp/attendance', [], ['Authorization' => 'Bearer '.$tokens['access_token']])
            ->assertUnauthorized();

        $this->postJson('/mcp/attendance', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'catalogo_sedes_departamentos', 'arguments' => []],
        ], ['Authorization' => 'Bearer '.$newAccess])->assertOk();

        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client['client_id'],
            'refresh_token' => $tokens['refresh_token'],
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_revocar_el_token_desde_el_panel_anula_el_refresh(): void
    {
        $user = $this->user();

        [$client, $tokens] = $this->tokensFor($user);

        PersonalAccessToken::findToken($tokens['access_token'])?->delete();

        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client['client_id'],
            'refresh_token' => $tokens['refresh_token'],
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_rechazar_el_consentimiento_devuelve_access_denied(): void
    {
        $user = $this->user();
        $client = $this->registerClient();
        $pkce = $this->pkce();

        $response = $this->actingAs($user)->post('/oauth/authorize', [
            'client_id' => $client['client_id'],
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'code_challenge' => $pkce[1],
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:use',
            'state' => 'abc',
            'decision' => 'deny',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('error=access_denied', (string) $response->headers->get('Location'));
        $this->assertStringContainsString('state=abc', (string) $response->headers->get('Location'));
        $this->assertDatabaseCount('oauth_authorization_codes', 0);
    }

    public function test_una_cuenta_inactiva_no_puede_autorizar(): void
    {
        $user = $this->user(['is_active' => false]);
        $client = $this->registerClient();

        $this->actingAs($user)
            ->get($this->authorizeUrl($client))
            ->assertStatus(400)
            ->assertSee('inactiva');
    }

    /**
     * @return array<string, mixed>
     */
    private function registerClient(): array
    {
        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => [self::REDIRECT],
        ]);

        $response->assertCreated();

        /** @var array<string, mixed> $client */
        $client = $response->json();

        return $client;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Encargado',
            'email' => 'encargado@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function pkce(): array
    {
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [$verifier, $challenge];
    }

    /**
     * @param  array<string, mixed>  $client
     */
    private function authorizeUrl(array $client): string
    {
        return '/oauth/authorize?'.http_build_query([
            'client_id' => $client['client_id'],
            'redirect_uri' => $client['redirect_uris'][0],
            'response_type' => 'code',
            'code_challenge' => $this->pkce()[1],
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:use',
            'state' => 'estado-123',
        ]);
    }

    /**
     * @param  array<string, mixed>  $client
     * @param  array{0: string, 1: string}  $pkce
     */
    private function codeFor(User $user, array $client, array $pkce): string
    {
        $response = $this->actingAs($user)->post('/oauth/authorize', [
            'client_id' => $client['client_id'],
            'redirect_uri' => $client['redirect_uris'][0],
            'response_type' => 'code',
            'code_challenge' => $pkce[1],
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:use',
            'state' => 'estado-123',
            'decision' => 'approve',
        ]);

        $response->assertRedirect();

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        return (string) ($query['code'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $client
     */
    private function exchange(array $client, string $code, string $verifier): TestResponse
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client['client_id'],
            'code' => $code,
            'redirect_uri' => $client['redirect_uris'][0],
            'code_verifier' => $verifier,
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function tokensFor(User $user): array
    {
        $client = $this->registerClient();
        $pkce = $this->pkce();
        $code = $this->codeFor($user, $client, $pkce);

        $this->assertNotSame('', $code);

        $tokens = $this->exchange($client, $code, $pkce[0])->assertOk();

        /** @var array<string, mixed> $payload */
        $payload = $tokens->json();

        return [$client, $payload];
    }
}
