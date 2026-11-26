<?php

namespace Tests\Feature;

use App\Models\OAuthAuthorizationCode;
use App\Models\OAuthClient;
use App\Models\OAuthRefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class McpOAuthPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_limpia_codigos_caducados_y_conserva_los_vigentes(): void
    {
        $client = $this->client();
        $vigente = $this->code($client, now()->addMinutes(5));
        $caducado = $this->code($client, now()->subMinute());

        $this->artisan('mcp:prune-oauth')->assertSuccessful();

        $this->assertModelExists($vigente);
        $this->assertModelMissing($caducado);
    }

    public function test_limpia_refresh_tokens_caducados_y_revocados_y_conserva_los_activos(): void
    {
        $client = $this->client();
        $activo = $this->refreshToken($client, now()->addDays(10));
        $caducado = $this->refreshToken($client, now()->subDay());
        $revocado = $this->refreshToken($client, now()->addDays(10), now()->subDay());

        $this->artisan('mcp:prune-oauth')->assertSuccessful();

        $this->assertModelExists($activo);
        $this->assertModelMissing($caducado);
        $this->assertModelMissing($revocado);
    }

    public function test_borra_clientes_sin_uso_pero_conserva_los_que_tienen_sesion(): void
    {
        $sinUso = $this->client(now()->subDays(40));
        $conSesion = $this->client(now()->subDays(40));
        $reciente = $this->client();

        $this->refreshToken($conSesion, now()->addDays(10));

        $this->artisan('mcp:prune-oauth')->assertSuccessful();

        $this->assertModelMissing($sinUso);
        $this->assertModelExists($conSesion);
        $this->assertModelExists($reciente);
    }

    public function test_un_cliente_conservado_sigue_renovando_su_token(): void
    {
        $user = $this->user();
        $client = $this->client(now()->subDays(40));
        $plain = 'refresh-cliente-conservado';

        $this->refreshToken($client, now()->addDays(10), userId: $user->id, plain: $plain);

        $this->artisan('mcp:prune-oauth')->assertSuccessful();

        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->client_id,
            'refresh_token' => $plain,
        ])->assertOk();
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Encargado',
            'email' => 'encargado@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);
    }

    private function client(?Carbon $createdAt = null): OAuthClient
    {
        $client = OAuthClient::create([
            'client_id' => 'mcp_'.Str::random(32),
            'name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
        ]);

        if ($createdAt !== null) {
            $client->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $client;
    }

    private function code(OAuthClient $client, Carbon $expiresAt): OAuthAuthorizationCode
    {
        return OAuthAuthorizationCode::create([
            'oauth_client_id' => $client->id,
            'user_id' => 1,
            'code_hash' => hash('sha256', Str::random(64)),
            'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'code_challenge' => str_repeat('a', 43),
            'expires_at' => $expiresAt,
        ]);
    }

    private function refreshToken(
        OAuthClient $client,
        Carbon $expiresAt,
        ?Carbon $revokedAt = null,
        int $userId = 1,
        ?string $plain = null,
    ): OAuthRefreshToken {
        return OAuthRefreshToken::create([
            'oauth_client_id' => $client->id,
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain ?? Str::random(40)),
            'expires_at' => $expiresAt,
            'revoked_at' => $revokedAt,
        ]);
    }
}
