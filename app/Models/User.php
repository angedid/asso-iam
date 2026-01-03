<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    const MERCHANT_USER = "MERCHANT_USER";
    const SELLER_USER = "SELLER_USER";
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    use HasUuids;

    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'parent_id'
        , 'type'
        , 'full_name'
        , 'username'
        , 'local_username'
        , 'email'
        , 'phone_number'
        , 'photo'
        , 'location'
        , 'region'
        , 'country'
        , 'email_verified_at'
        , 'password'
        , 'active'
        , 'network'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Find the user instance for the given username.
     */
    /*public function findForPassport(string $username, Client $client): User
    {
        return $this->where('username', $username)->first();
    }*/

    /**
     * Prepare the access token for storage.
     *
     * @param  \Laravel\Passport\OAuth2\AccessToken $accessToken
     * @return void
     */
    /*public function withAccessToken($accessToken): static
    {
        // Dynamically retrieve scopes from your database or other source
        // e.g., if you have a relationship to a user_scopes table
        $roles = Role::where("tenant_id", $this->tenant_id)->get();
        $scopes = [];
        foreach ($roles as $role) {
            if ($role->isUserPlayingRole($this->id)) {
                $roleScopes = json_decode($role->scopes, true);
                for($i = 0; $i < count($roleScopes); $i++){
                    $scopes[] = $roleScopes[$i];
                }
            }
        }
        $scopesStr = implode(' ', $scopes);

        Log::info("IN USER SCOPES=$scopesStr");

        //$dynamicScopes = $this->userScopes()->pluck('scope_name')->toArray();

        // Assign the scopes to the token
        $accessToken->scopes = $scopesStr;

        $accessToken->save();

        Log::info("IN ACCESS TOKEN=" . $accessToken->toJson());

        $this->save();

        return $this;
    }*/

    /*public function validateForPassportPasswordGrant($password): bool
    {
        //Log::info("validateForPassportPasswordGrant");
        return Hash::check($password, $this->password) && $this->username === request('username') && $this->tenant_id === request('tenant_id');
    }*/


}
