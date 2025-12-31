<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class OauthAuthCode extends Model
{
    protected $table = 'oauth_auth_codes';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
        'id'
        , 'user_id'
        , 'client_id'
        , 'scopes'
        , 'revoked'
        , 'expires_at'
    ];
}
