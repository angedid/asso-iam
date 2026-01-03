<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Nanorocks\LicenseManager\Models\License;
use Nanorocks\LicenseManager\Services\LicenseService;

class Tenant extends Model
{
    public const DEFAULT_TENANT_ID = "f733f002-53ec-4f82-aaa3-7374fc728b87";
    protected $table = 'tenants';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'name'
        , 'city'
        , 'location'
        , 'region'
        , 'country'
        , 'logo'
        , 'phone_number'
        , 'website'
        , 'address'
        , 'active'
        , 'taxpayer_number'
        , 'number_trade_register'
        , 'license_id'
        , 'administrator'
        , 'network'
    ];

    /*public function isValidTenant(): bool
    {
        return $this->active;
    }*/
}
