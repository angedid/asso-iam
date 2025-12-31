<?php

namespace App\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'tenant_id'
        , 'name'
        , 'description'
        , 'members'
        , 'active'
    ];

    public function isUserMember(string $user_id): bool
    {
        $members = json_decode($this->members);
        foreach ($members as $member) {
            if($member == $user_id) return true;
            $group = Group::where("id", $member)->first();
            if($group !== null && $group->isUserMember($user_id)) return true;
        }
        return false;
    }

    public function isGroupMember(string $grp_id): bool
    {
        $members = json_decode($this->members);
        foreach ($members as $member) {
            if($member == $grp_id) return true;
            $group = Group::where("id", $member)->first();
            if($group !== null && $group->isGroupMember($grp_id)) return true;
        }
        return false;
    }

    public function removeMember(string $grp_id){
       $members = json_decode($this->members, true);
       if(in_array($grp_id, $members)){
           $newMembers = array_diff($members, [$grp_id]);
           $this->members = json_encode($newMembers);
           $this->save();
           return;
       }
       forEach($members as $member){
           $group = Group::where("id", $member)->first();
           if($group !== null && $group->isGroupMember($grp_id)){
               $group->removeMember($grp_id);
           }
       }
    }

}
