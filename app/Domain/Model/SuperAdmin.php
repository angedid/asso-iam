<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SuperAdmin extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $guarded = ['id'];

    protected $table = 'super_admins';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'email', 'username', 'phone_number', 'password', 'active', 'is_super_admin'];

    protected $hidden = [
        'password',
    ];


}
