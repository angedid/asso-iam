<?php

namespace App\Domain\Model;


use Illuminate\Support\Carbon;
use Nanorocks\LicenseManager\Models\License;

class AssoLicense extends License
{
    protected $table = 'licenses';
    //protected $primaryKey = 'id';
    //protected $casts = ['id' => 'string'];
    //public $incrementing = false;
    //protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'license_key'
        , 'assigned_to'
        , 'tenant_id'
        , 'is_trial'
        , 'users'
        , 'active'
        , 'expires_at'
        , 'metadata'
        , 'duration'
        , 'amount'
        , 'order_id'
    ];

}

