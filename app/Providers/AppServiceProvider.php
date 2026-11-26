<?php

namespace App\Providers;

use App\Models\OAuthRefreshToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Al revocar un token MCP (desde /mi-mcp o /admin/mcp) también se anula
        // el refresh token OAuth que lo acompaña, para que no pueda renovarse.
        PersonalAccessToken::deleting(function (PersonalAccessToken $token): void {
            OAuthRefreshToken::where('personal_access_token_id', $token->getKey())->update(['revoked_at' => now()]);
        });

        RateLimiter::for('mcp-token', function (Request $request) {
            $accessToken = $request->user()?->currentAccessToken();
            $tokenId = $accessToken instanceof PersonalAccessToken ? $accessToken->getKey() : null;

            $key = $tokenId !== null
                ? 'token:'.$tokenId
                : 'user:'.($request->user()?->getAuthIdentifier() ?? $request->ip());

            return Limit::perMinute(60)->by('mcp:'.$key);
        });

        // En entorno de escritorio local NativePHP nunca forzar HTTPS (el servidor interno corre en HTTP)
        if (config('nativephp-internal.running') || env('NATIVEPHP_RUNNING')) {
            URL::forceScheme('http');

            return;
        }

        if (config('app.env') === 'production' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }
    }
}
