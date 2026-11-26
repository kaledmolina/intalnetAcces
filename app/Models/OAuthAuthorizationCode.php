<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthAuthorizationCode extends Model
{
    protected $table = 'oauth_authorization_codes';

    protected $fillable = [
        'oauth_client_id',
        'user_id',
        'code_hash',
        'redirect_uri',
        'code_challenge',
        'expires_at',
        'consumed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
