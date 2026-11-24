<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class McpAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_el_superadmin_accede_al_panel_mcp(): void
    {
        $tenant = User::create([
            'name' => 'Tenant',
            'email' => 'tenant@example.com',
            'password' => 'secret-password',
        ]);

        $this->actingAs($tenant)->get('/admin/mcp')->assertForbidden();
    }

    public function test_el_superadmin_genera_un_token_de_solo_lectura_para_un_usuario(): void
    {
        $admin = User::create([
            'name' => 'Superadmin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_superadmin' => true,
        ]);

        $target = User::create([
            'name' => 'Encargado',
            'email' => 'encargado@example.com',
            'password' => 'secret-password',
        ]);

        $this->actingAs($admin)->get('/admin/mcp')->assertOk();

        $response = $this->actingAs($admin)->post('/admin/mcp/tokens', [
            'user_id' => $target->id,
            'name' => 'freebuff',
        ]);

        $response->assertRedirect(route('mcp.index'));
        $response->assertSessionHas('mcp_plain_text_token');

        $token = PersonalAccessToken::where('tokenable_id', $target->id)->firstOrFail();

        $this->assertSame('freebuff', $token->name);
        $this->assertContains('mcp:read', $token->abilities);
    }

    public function test_un_usuario_genera_su_propio_token_sin_poder_elegir_otro_usuario(): void
    {
        $user = User::create([
            'name' => 'Encargado',
            'email' => 'encargado@example.com',
            'password' => 'secret-password',
        ]);

        $other = User::create([
            'name' => 'Otra Empresa',
            'email' => 'otra@example.com',
            'password' => 'secret-password',
        ]);

        $this->actingAs($user)->get('/mi-mcp')->assertOk();

        $response = $this->actingAs($user)->post('/mi-mcp/tokens', [
            'name' => 'mi-laptop',
            'user_id' => $other->id,
        ]);

        $response->assertRedirect(route('mcp.self'));
        $response->assertSessionHas('mcp_plain_text_token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'mi-laptop',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $other->id,
        ]);
    }

    public function test_un_usuario_no_puede_revocar_el_token_de_otro(): void
    {
        $user = User::create([
            'name' => 'Encargado',
            'email' => 'encargado@example.com',
            'password' => 'secret-password',
        ]);

        $other = User::create([
            'name' => 'Otra Empresa',
            'email' => 'otra@example.com',
            'password' => 'secret-password',
        ]);

        $otherToken = $other->createToken('ajeno', ['mcp:read'])->accessToken;

        $this->actingAs($user)
            ->delete(route('mcp.self.tokens.destroy', $otherToken))
            ->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
    }

    public function test_el_superadmin_revoca_un_token_mcp(): void
    {
        $admin = User::create([
            'name' => 'Superadmin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_superadmin' => true,
        ]);

        $token = $admin->createToken('freebuff', ['mcp:read'])->accessToken;

        $this->actingAs($admin)
            ->delete(route('mcp.tokens.destroy', $token))
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }
}
