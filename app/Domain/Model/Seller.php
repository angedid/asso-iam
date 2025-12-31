<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class Seller extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    use HasUuids;
    protected $guarded = ['id'];

    protected $table = 'sellers';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'full_name'
        , 'username'
        , 'email'
        , 'email_verified_at'
        , 'password'
        , 'phone_number'
        , 'photo'
        , 'location'
        , 'region'
        , 'country'
        , 'network'
        , 'active'
    ];

    protected $hidden = [
        'password',
    ];
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Find the user instance for the given username.
     */
    /*public function findForPassport(string $username, Client $client): Seller
    {
        return $this->where('username', $username)->where('active', true)->first();
    }

    public function validateForPassportPasswordGrant($password)
    {

        return $this->active && Hash::check($password, $this->password);
    }*/

}
