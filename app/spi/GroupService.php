<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\DataOrError;
use App\Domain\Model\Group;
use App\Domain\Model\GroupMember;
use App\Jobs\ProcessMessages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupService
{

    protected DomainServices $domainServices;

    public function __construct(DomainServices $domainServices)
    {
        $this->domainServices = $domainServices;
    }

    public function createNewGroup(Request $request): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $validatorGroup = DomainServices::makeGroupValidator($request);

        if ($validatorGroup->fails()) {
            return new DataOrError(null, $validatorGroup->errors()->all(),true);
        }

        $group = Group::where('name', trim($request->get('name')))
            ->where('tenant_id', trim($request->get('tenant_id')))->first();
        if ($group !== null){
            return new DataOrError(null, [__("Nom deja utilise pour cette entreprise.")],true);
        }

        $groupid = Str::uuid()->toString();

        $groupData = [
            'id' => $groupid,
            'tenant_id' => trim($request->get('tenant_id')),
            'name' => trim($request->get('name')),
            'description' => trim($request->get('description')),
            'members' => json_encode([]),
            'active' => true,
        ];

        $groupe = Group::create($groupData);

        $dataOrError = new DataOrError(['group' => $groupe], null,false);

        ProcessMessages::dispatch(env('GROUP_CREATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function retrieveAllGroup(Request $request): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $user = $request->user();
        return new DataOrError(['groups' => Group::where('tenant_id', $user->tenant_id)->get()], null,false);
    }

    public function addGroupMemberToGroup(Request $request, string $groupid): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();

        if ($user->tenant_id !== $request->get('tenant_id')){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }

        $group = Group::where('id', trim($groupid))->where('tenant_id', $request->get('tenant_id'))->first();
        if($group === null){
            return new DataOrError(null, [__("Groupe inconnu!")],true);
        }
        $member = User::where('id', trim($request->get('member_id')))->where('tenant_id', $request->get('tenant_id'))->first();
        if($member === null){
            $member = Group::where('id', trim($request->get('member_id')))->where('tenant_id', $request->get('tenant_id'))->first();
            if($member === null){
                return new DataOrError(null, [__("Membre inconnu!")],true);
            }
        }

        if ($group->isUserMember(trim($request->get('member_id'))) || $group->isGroupMember(trim($request->get('member_id')))) {
            return new DataOrError(null, [__("Le membre deja le groupe!")],true);
        }

        $members = json_decode($group->members, true);
        $members[] = trim($request->get('member_id'));
        $group->members = json_encode($members);
        $group->save();
        $dataOrError = new DataOrError(['group' => $group], null,false);
        ProcessMessages::dispatch(env('MEMBER_ADDED_TO_GROUP_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
        return $dataOrError;
    }

    public function retrieveGroupMembers(Request $request, string $groupid): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $user = $request->user();

        $group = Group::where('id', trim($groupid))->first();
        if($group === null){
            return new DataOrError(null, [__("Groupe inconnu!")],true);
        }
        $members = json_decode($group->members, true);

        $membres = [];
        foreach($members as $member){
            $membre = User::where('id', $member)->first();
            $is_group = false;
            if($membre === null){
                $membre = Group::where('id', $member)->first();
                $is_group = true;
            }
            $membres[] = new GroupMember($member, $groupid, $is_group, $membre);
        }
        return new DataOrError(['group_members' => $membres], null,false);
    }

    public function notGroupMembers(Request $request, string $groupid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $user = $request->user();
        $group = Group::where('id', trim($groupid))->first();
        if($group === null){
            return new DataOrError(null, [__("Groupe inconnu!")],true);
        }
        $members = json_decode($group->members, true);
        $users = User::whereNotIn('id', $members)->get();
        $membres = [];
        foreach($users as $user){
            if (!$group->isUserMember($user->id)) {
                $membres[] = new GroupMember($user->id, $groupid, false, $user);
            }
        }
        $groups = Group::whereNotIn('id', $members)->get();
        foreach($groups as $grp){
            if (!$group->isGroupMember($grp->id)) {
                $membres[] = new GroupMember($grp->id, $groupid, true, $grp);
            }
        }
        return new DataOrError(['group_members' => $membres], null,false);
    }

    public function removeGroupMemberToGroup(Request $request, string $groupid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();

        if ($user->tenant_id !== $request->get('tenant_id')){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }

        $group = Group::where('id', trim($groupid))->where('tenant_id', $request->get('tenant_id'))->first();
        if($group === null){
            return new DataOrError(null, [__("Groupe inconnu!")],true);
        }
        $member = User::where('id', trim($request->get('member_id')))->where('tenant_id', $request->get('tenant_id'))->first();
        if($member === null){
            $member = Group::where('id', trim($request->get('member_id')))->where('tenant_id', $request->get('tenant_id'))->first();
            if($member === null){
                return new DataOrError(null, [__("Membre inconnu!")],true);
            }
        }

        if (!$group->isUserMember(trim($request->get('member_id'))) && !$group->isGroupMember(trim($request->get('member_id')))) {
            return new DataOrError(null, [__("Le l'utilisateur ou le groupe n'est pas mengre de ce groupe!")],true);
        }

        //$members = json_decode($group->members, true);
        /*foreach($members as $member){
            $theUser = User::where('id', $member)->first();
            if ($theUser !== null){
                $newMembers = [];
                foreach($members as $member){
                    if ($theUser->id !== $member){
                        $newMembers[] = $theUser;
                    }
                }
                $group->members = json_encode($newMembers);
            }
        }*/

        $group->removeMember(trim($request->get('member_id')));
        //$members[] = trim($request->get('member_id'));
        //$group->members = json_encode($members);
        $group->save();
        $dataOrError = new DataOrError(['group' => $group], null,false);
        ProcessMessages::dispatch(env('MEMBER_REMOVED_FROM_GROUP_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
        return $dataOrError;
    }


}
