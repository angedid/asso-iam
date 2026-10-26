<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class LicensePayement extends Model
{
    protected $table = 'license_payments';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'license_id'
        , 'payment_id'
        , 'amount'
        , 'date'
        , 'payment_method'
        , 'payment_status'
        , 'currency'
        , 'currency_symbol'
        , 'signature'
    ];


}
