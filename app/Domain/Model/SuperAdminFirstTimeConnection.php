<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class SuperAdminFirstTimeConnection extends Model
{
    protected $table = 'super_admin_first_time_connections';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'has_been_connected',];
}
