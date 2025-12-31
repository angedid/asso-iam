<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class UserRegistrationInvitation extends Model
{
    protected $table = 'users_registration_invitations';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'full_name'
        , 'email'
        , 'phone_number'
        , 'invited_by'
        , 'invited_at'
        , 'expires_at'
        , 'active'
        , 'state'
        , 'used'
        , 'used_at'
        , 'url'
    ];

    public function isValid() : bool{
        if (!$this->active){
            return false;
        }
        $expires_at = Carbon::parse($this->expires_at);
        $now = Carbon::now();
        if ($expires_at->isBefore($now)){
            return false;
        }
        return true;
    }
}
