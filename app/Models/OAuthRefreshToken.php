<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthRefreshToken extends Model
{
    protected $table = 'oauth_refresh_tokens';

    protected $fillable = [
        'oauth_client_id',
        'user_id',
        'token_hash',
        'personal_access_token_id',
        'expires_at',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
