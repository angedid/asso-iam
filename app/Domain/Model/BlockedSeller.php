<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class BlockedSeller extends Model
{
    protected $table = 'blocked_sellers';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'tenant_id'
        , 'seller_id'
        , 'blocked_by'
        , 'blocked_at'
    ];
}

