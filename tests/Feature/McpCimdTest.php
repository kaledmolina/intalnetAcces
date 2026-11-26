<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CimdClientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class McpCimdTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'https://claude.ai/oauth/client-metadata.json';

    private const REDIRECT = 'https://claude.ai/api/mcp/auth_callback';

    public function test_anuncia_cimd_y_muestra_la_identidad_publicada(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('client_id_metadata_document_supported', true);

        $this->allowDocumentFetch();
        $this->fakeDocument();

        $this->actingAs($this->user())
            ->get($this->authorizeUrl())
            ->assertOk()
            ->assertSee('Claude')
            ->assertSee('Identidad declarada')
            ->assertSee(self::REDIRECT);
    }

    public function test_el_flujo_cimd_emite_un_token_utilizable(): void
    {
        $this->allowDocumentFetch();
        $this->fakeDocument();

        $user = $this->user();
        $pkce = $this->pkce();

        $approve = $this->actingAs($user)->post('/oauth/authorize', array_merge($this->params($pkce), [
            'decision' => 'approve',
        ]));

        $approve->assertRedirect();

        parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('code', $query);
        $this->assertDatabaseHas('oauth_clients', [
            'client_id' => self::CLIENT_ID,
            'name' => 'Claude',
        ]);

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => self::CLIENT_ID,
            'code' => $query['code'],
            'redirect_uri' => self::REDIRECT,
            'code_verifier' => $pkce[0],
        ])->assertOk();

        $this->postJson('/mcp/attendance', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'catalogo_sedes_departamentos',
                'arguments' => [],
            ],
        ], [
            'Authorization' => 'Bearer '.$token->json('access_token'),
        ])
            ->assertOk()
            ->assertJsonPath('result.structuredContent.herramienta', 'catalogo_sedes_departamentos');
    }

    public function test_rechaza_un_documento_que_no_coincide_con_su_url(): void
    {
        $this->allowDocumentFetch();
        $this->fakeDocument(['client_id' => 'https://otro.example/client.json']);

        $this->actingAs($this->user())
            ->get($this->authorizeUrl())
            ->assertStatus(400)
            ->assertSee('identidad pública');
    }

    public function test_rechaza_documentos_que_exigen_secreto_de_cliente(): void
    {
        $this->allowDocumentFetch();
        $this->fakeDocument([
            'token_endpoint_auth_method' => 'client_secret_post',
            'token_endpoint_auth_methods_supported' => ['client_secret_post'],
        ]);

        $this->actingAs($this->user())
            ->get($this->authorizeUrl())
            ->assertStatus(400);
    }

    public function test_acepta_el_documento_de_chatgpt_porque_ofrece_none(): void
    {
        $this->allowDocumentFetch();
        $this->fakeDocument([
            'client_name' => 'ChatGPT',
            'token_endpoint_auth_method' => 'private_key_jwt',
            'token_endpoint_auth_methods_supported' => ['none', 'private_key_jwt'],
            'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
        ]);

        $this->actingAs($this->user())
            ->get($this->authorizeUrl(self::CLIENT_ID, 'https://chatgpt.com/connector_platform_oauth_redirect'))
            ->assertOk()
            ->assertSee('ChatGPT');
    }

    public function test_acepta_puertos_efimeros_en_redirects_loopback(): void
    {
        $this->allowDocumentFetch();
        $this->fakeDocument([
            'client_name' => 'Claude Code',
            'redirect_uris' => ['http://localhost/callback', 'http://127.0.0.1/callback'],
        ]);

        $this->actingAs($this->user())
            ->get($this->authorizeUrl(self::CLIENT_ID, 'http://localhost:52123/callback'))
            ->assertOk()
            ->assertSee('Claude Code');
    }

    public function test_el_documento_no_autoriza_otra_redirect_uri(): void
    {
        $this->allowDocumentFetch();
        $this->fakeDocument(['redirect_uris' => ['https://claude.ai/otra/ruta']]);

        $this->actingAs($this->user())
            ->get($this->authorizeUrl())
            ->assertStatus(400)
            ->assertSee('dirección de retorno');
    }

    public function test_no_descarga_documentos_de_la_red_interna(): void
    {
        Http::fake();

        $user = $this->user();

        $this->actingAs($user)
            ->get($this->authorizeUrl('https://127.0.0.1/client.json'))
            ->assertStatus(400);

        $this->actingAs($user)
            ->get($this->authorizeUrl('http://claude.ai/client.json'))
            ->assertStatus(400);

        Http::assertNothingSent();
    }

    /**
     * Evita DNS real en las pruebas: el documento se sirve con Http::fake.
     */
    private function allowDocumentFetch(): void
    {
        $this->app->bind(CimdClientResolver::class, fn (): CimdClientResolver => new class extends CimdClientResolver
        {
            protected function isPublicHttpsUrl(string $url): bool
            {
                return true;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeDocument(array $overrides = []): void
    {
        Http::fake([
            self::CLIENT_ID => Http::response(array_merge([
                'client_id' => self::CLIENT_ID,
                'client_name' => 'Claude',
                'redirect_uris' => [self::REDIRECT],
                'token_endpoint_auth_method' => 'none',
            ], $overrides)),
        ]);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Encargado',
            'email' => 'cimd@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);
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

    private function authorizeUrl(string $clientId = self::CLIENT_ID, string $redirectUri = self::REDIRECT): string
    {
        return '/oauth/authorize?'.http_build_query($this->params($this->pkce(), $clientId, $redirectUri));
    }

    /**
     * @param  array{0: string, 1: string}  $pkce
     * @return array<string, string>
     */
    private function params(array $pkce, string $clientId = self::CLIENT_ID, string $redirectUri = self::REDIRECT): array
    {
        return [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'code_challenge' => $pkce[1],
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:use',
            'state' => 'xyz',
        ];
    }
}
