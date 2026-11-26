<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\OAuthAuthorizationCode;
use App\Models\OAuthClient;
use App\Models\OAuthRefreshToken;
use App\Models\User;
use App\Support\McpOAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    /**
     * Canje del código de autorización y renovación por refresh token.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $clientId = (string) ($request->input('client_id') ?: $request->getUser());

        $client = $clientId === '' ? null : OAuthClient::where('client_id', $clientId)->first();

        if (! $client) {
            return $this->error('invalid_client', 'Cliente OAuth desconocido.', 401);
        }

        return match ((string) $request->input('grant_type')) {
            'authorization_code' => $this->authorizationCode($request, $client),
            'refresh_token' => $this->refreshToken($request, $client),
            default => $this->error('unsupported_grant_type', 'Solo se admiten authorization_code y refresh_token.'),
        };
    }

    private function authorizationCode(Request $request, OAuthClient $client): JsonResponse
    {
        $code = (string) $request->input('code');
        $verifier = (string) $request->input('code_verifier');
        $redirectUri = (string) $request->input('redirect_uri');

        if ($code === '' || $verifier === '') {
            return $this->error('invalid_request', 'Faltan code o code_verifier.');
        }

        $record = OAuthAuthorizationCode::where('code_hash', hash('sha256', $code))->first();

        if (! $record || (int) $record->oauth_client_id !== (int) $client->id) {
            return $this->error('invalid_grant', 'El código de autorización no es válido.');
        }

        if ($record->consumed_at !== null || $record->expires_at->isPast()) {
            return $this->error('invalid_grant', 'El código de autorización ya se usó o expiró.');
        }

        if (! hash_equals($record->redirect_uri, $redirectUri)) {
            return $this->error('invalid_grant', 'La redirect_uri no coincide con la autorización.');
        }

        if (! McpOAuth::verifyPkce($verifier, $record->code_challenge)) {
            return $this->error('invalid_grant', 'El code_verifier no corresponde al code_challenge.');
        }

        $claimed = OAuthAuthorizationCode::whereKey($record->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        if ($claimed !== 1) {
            return $this->error('invalid_grant', 'El código de autorización ya se usó.');
        }

        $user = User::find($record->user_id);

        if (! $user || ! $user->is_active) {
            return $this->error('invalid_grant', 'La cuenta ya no está activa.');
        }

        return DB::transaction(fn (): JsonResponse => $this->issueTokens($user, $client));
    }

    private function refreshToken(Request $request, OAuthClient $client): JsonResponse
    {
        $plain = (string) $request->input('refresh_token');

        if ($plain === '') {
            return $this->error('invalid_request', 'Falta el refresh_token.');
        }

        $record = OAuthRefreshToken::where('token_hash', hash('sha256', $plain))->first();

        if (! $record || (int) $record->oauth_client_id !== (int) $client->id) {
            return $this->error('invalid_grant', 'El refresh token no es válido.');
        }

        if ($record->revoked_at !== null || $record->expires_at->isPast()) {
            return $this->error('invalid_grant', 'El refresh token expiró o fue revocado.');
        }

        $user = User::find($record->user_id);

        if (! $user || ! $user->is_active) {
            return $this->error('invalid_grant', 'La cuenta ya no está activa.');
        }

        return DB::transaction(function () use ($record, $user, $client): JsonResponse {
            $record->forceFill(['revoked_at' => now()])->save();

            if ($record->personal_access_token_id !== null) {
                PersonalAccessToken::find($record->personal_access_token_id)?->delete();
            }

            return $this->issueTokens($user, $client);
        });
    }

    /**
     * Emite un token Sanctum de solo lectura y su refresh token.
     */
    private function issueTokens(User $user, OAuthClient $client): JsonResponse
    {
        $access = $user->createToken(
            mb_substr($client->name, 0, 40).' (OAuth)',
            [McpOAuth::ABILITY],
            now()->addMinutes(McpOAuth::ACCESS_TOKEN_MINUTES),
        );

        $refresh = bin2hex(random_bytes(40));

        OAuthRefreshToken::create([
            'oauth_client_id' => $client->id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refresh),
            'personal_access_token_id' => $access->accessToken->getKey(),
            'expires_at' => now()->addDays(McpOAuth::REFRESH_TOKEN_DAYS),
        ]);

        return McpOAuth::json([
            'access_token' => $access->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => McpOAuth::ACCESS_TOKEN_MINUTES * 60,
            'refresh_token' => $refresh,
            'scope' => McpOAuth::SCOPE,
        ]);
    }

    private function error(string $error, string $description, int $status = 400): JsonResponse
    {
        return McpOAuth::json([
            'error' => $error,
            'error_description' => $description,
        ], $status);
    }
}
