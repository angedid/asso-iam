<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'name'
        , 'description'
        , 'groups_playing_role'
        , 'users_playing_role'
        , 'scopes'
        , 'active'
    ];

    public function isUserPlayingRole(string $user_id): bool
    {
        $usersInrole = json_decode($this->users_playing_role);
        if (in_array($user_id, $usersInrole)) {
            return true;
        }
        $groupsPlayingRole = json_decode($this->groups_playing_role);
        foreach ($groupsPlayingRole as $group_id) {
            $group = Group::where("id", $group_id)->first();
            if($group->isUserMember($user_id)) {
                return true;
            }
        }
        return false;
    }

    public function isGroupPlayingRole(string $grp_id): bool
    {
        $groupsPlayingRole = json_decode($this->groups_playing_role);
        foreach ($groupsPlayingRole as $group_id) {
            if($group_id == $grp_id) {
                return true;
            }

            $group = Group::where("id", $group_id)->first();
            if($group->isGroupMember($grp_id)) {
                return true;
            }
        }
        return false;
    }
}
