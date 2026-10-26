<?php

namespace App\Domain\Model;

use App\Constances;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PasswordResetInvitation extends Model
{
    protected $table = 'password_reset_invitations';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'username'
        , 'email'
        , 'expires_at'
        , 'active'
        , 'state'
        , 'used'
        , 'used_at'
        , 'url'
    ];

    public function isValidForUsage(): int{
        if($this->active === false) return 1;
        if (Carbon::parse($this->expires_at)->isBefore(Carbon::now())) return 2;
        if ($this->state !== Constances::PENDING) return 3;
        if ($this->used) return 4;
        return 0;
    }

}
