<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class SellerRequestToJoinNetwork extends Model
{

    protected $table = 'sellers_requests_to_join_networks';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'network_admin'
        , 'requester_id'
        , 'invited_at'
        , 'expires_at'
        , 'active'
        , 'state'
        , 'used'
        , 'used_at'
        , 'url'
    ];
}
