<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Scope extends Model
{
    protected $table = 'scopes';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'key'
        , 'description'
        , 'active'
    ];
}
