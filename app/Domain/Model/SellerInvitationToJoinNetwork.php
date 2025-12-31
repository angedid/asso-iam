<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class SellerInvitationToJoinNetwork extends Model
{
    protected $table = 'sellers_invitations_to_join_networks';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'inviter_id'
        , 'invitee_id'
        , 'invited_at'
        , 'expires_at'
        , 'active'
        , 'state'
        , 'used'
        , 'used_at'
        , 'url'
    ];
}
