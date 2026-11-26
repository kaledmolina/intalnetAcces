<?php

namespace App\Console\Commands;

use App\Models\OAuthAuthorizationCode;
use App\Models\OAuthClient;
use App\Models\OAuthRefreshToken;
use Illuminate\Console\Command;

class PruneMcpOAuth extends Command
{
    /** Días que se conserva un cliente registrado que nunca completó una autorización. */
    private const CLIENT_IDLE_DAYS = 30;

    protected $signature = 'mcp:prune-oauth';

    protected $description = 'Borra códigos y refresh tokens caducados y clientes OAuth que nunca se usaron.';

    public function handle(): int
    {
        // Un código dura minutos: al expirar ya no sirve para canjear ni para auditar.
        $codes = OAuthAuthorizationCode::where('expires_at', '<', now())->delete();

        // Un refresh revocado es inservible de inmediato; el caducado también.
        $refreshTokens = OAuthRefreshToken::where('expires_at', '<', now())
            ->orWhereNotNull('revoked_at')
            ->delete();

        // Clientes que se registraron y nunca llegaron a autorizar nada.
        $clients = OAuthClient::where('created_at', '<', now()->subDays(self::CLIENT_IDLE_DAYS))
            ->whereDoesntHave('authorizationCodes')
            ->whereDoesntHave('refreshTokens')
            ->delete();

        $this->info("Códigos caducados eliminados: {$codes}");
        $this->info("Refresh tokens caducados o revocados eliminados: {$refreshTokens}");
        $this->info("Clientes OAuth sin uso eliminados: {$clients}");

        return self::SUCCESS;
    }
}
