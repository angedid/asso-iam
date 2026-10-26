<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\DataOrError;
use App\Domain\Model\Group;
use App\Domain\Model\Role;
use App\Domain\Model\Scope;
use App\Domain\Model\Tenant;
use App\Jobs\ProcessMessages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleService
{

    protected DomainServices $domainServices;

    public function __construct(DomainServices $domainServices)
    {
        $this->domainServices = $domainServices;
    }

    public function isUserPlayingRole($roleid, $userid){
        $role = Role::where("id", $roleid)->first();
        if($role === null){
            return false;
        }
        return $role->isUserPlayingRole($userid);
    }



    public function createNewRole(Request $request): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $validatorRole = DomainServices::makeRoleValidator($request);

        if ($validatorRole->fails()) {
            return new DataOrError(null, $validatorRole->errors()->all(),true);
        }

        $role = Role::where('name', trim($request->get('name')))
            ->where('tenant_id', trim($request->get('tenant_id')))->first();
        if ($role !== null){
            return new DataOrError(null, [__("Nom deja utilise pour cette entreprise.")],true);
        }

        $roleid = Str::uuid()->toString();

        $roleData = [
            'id' => $roleid,
            'tenant_id' => trim($request->get('tenant_id')),
            'name' => trim($request->get('name')),
            'description' => trim($request->get('description')),
            'groups_playing_role' => json_encode([]),
            'users_playing_role' => json_encode([]),
            'scopes' => json_encode([]),
            'active' => true,
        ];

        $role = Role::create($roleData);

        $dataOrError = new DataOrError(['role' => $role], null,false);

        ProcessMessages::dispatch(env('ROLE_CREATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function retrieveAllRoles(Request $request): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $user = $request->user();
        return new DataOrError(['roles' => Role::where('tenant_id', $user->tenant_id)->get()], null,false);
    }

    public function addGroupToRole(Request $request, string $roleid): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $groupReq = json_decode($request->getContent(), true);
        $user = $request->user();
        if ($user->tenant_id !== $groupReq['tenant_id']){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }
        $role = Role::where('id', trim($roleid))->where('tenant_id', $groupReq['tenant_id'])->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $group = Group::where('id', trim($groupReq['group_id']))->where('tenant_id', $groupReq['tenant_id'])->first();
        if($group === null){
            return new DataOrError(null, [__("Groupe inconnu!")],true);
        }

        if ($role->isGroupPlayingRole($group->id)){
            return new DataOrError(null, [__("Le groupe joue deja le role!")],true);
        }

        $currentGroups = json_decode($role->groups_playing_role, true);
        $currentGroups[] = $group->id;
        $role->groups_playing_role = json_encode($currentGroups);
        $role->save();
        $dataOrError = new DataOrError(['role' => $role], null,false);
        ProcessMessages::dispatch(env('GROUP_ADDED_TO_ROLE_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function removeGroupInRole(Request $request, string $roleid): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $groupReq = json_decode($request->getContent(), true);
        $user = $request->user();
        if ($user->tenant_id !== $groupReq['tenant_id']){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }
        $role = Role::where('id', trim($roleid))->where('tenant_id', $groupReq['tenant_id'])->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $group = Group::where('id', trim($groupReq['group_id']))->where('tenant_id', $groupReq['tenant_id'])->first();
        if($group === null){
            return new DataOrError(null, [__("Groupe inconnu!")],true);
        }

        if (!$role->isGroupPlayingRole($group->id)){
            return new DataOrError(null, [__("Le groupe ne joue pas le role!")],true);
        }

        $currentGroups = json_decode($role->groups_playing_role, true);
        if (in_array($group->id, $currentGroups)){
            $newCurrentGroups  = [];
            foreach ($currentGroups as $grp){
                if ($grp !== $group->id){
                    $newCurrentGroups[] = $grp;
                }
            }
            $role->groups_playing_role = json_encode($newCurrentGroups);
            $role->save();
            $dataOrError = new DataOrError(['role' => $role], null,false);
            ProcessMessages::dispatch(env('GROUP_REMOVED_FROM_ROLE_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
            return $dataOrError;
        }

        foreach ($currentGroups as $grp){
            $theGroup = Group::where('id', trim($grp))->first();
            if($theGroup !== null && $theGroup->isGroupMember($group->id)){
                $theGroup->removeMember($group->id);
            }
        }

        $dataOrError = new DataOrError(['role' => $role], null,false);
        ProcessMessages::dispatch(env('GROUP_REMOVED_FROM_ROLE_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function removeUserInRole(Request $request, string $roleid): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();
        if ($user->tenant_id !== $request->get('tenant_id')){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }
        $role = Role::where('id', trim($roleid))->where('tenant_id', $request->get('tenant_id'))->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $theUsr = User::where('id', trim($request->get('user_id')))->where('tenant_id', $request->get('tenant_id'))->first();
        if($theUsr === null){
            return new DataOrError(null, [__("Utilisateur inconnu!")],true);
        }

        if (!$role->isUserPlayingRole($theUsr->id)){
            return new DataOrError(null, [__("L'utilisateur ne joue pas le role!")],true);
        }

        $currentUsers = json_decode($role->users_playing_role, true);
        if (in_array($theUsr->id, $currentUsers)){
            $newCurrentUsers  = [];
            foreach ($currentUsers as $usr){
                if ($usr !== $theUsr->id){
                    $newCurrentUsers[] = $usr;
                }
            }
            $role->users_playing_role = json_encode($newCurrentUsers);
            $role->save();
            $dataOrError = new DataOrError(['role' => $role], null,false);
            ProcessMessages::dispatch(env('USER_REMOVED_FROM_ROLE_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
            return $dataOrError;
        }
        return new DataOrError(['role' => $role], null,false);
    }

    public function removeScopeInRole(Request $request, string $roleid): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();
        if ($user->tenant_id !== $request->get('tenant_id')){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }
        $role = Role::where('id', trim($roleid))->where('tenant_id', $request->get('tenant_id'))->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $theScope = Scope::where('key', trim($request->get('scope_key')))->first();
        if($theScope === null){
            return new DataOrError(null, [__("Porte inconnu!")],true);
        }

        $currentScopes = json_decode($role->scopes, true);
        if (in_array($theScope->key, $currentScopes)){
            $newCurrentScopes  = [];
            foreach ($currentScopes as $key){
                if ($key !== $theScope->key){
                    $newCurrentScopes[] = $key;
                }
            }
            $role->scopes = json_encode($newCurrentScopes);
            $role->save();
            $dataOrError = new DataOrError(['role' => $role], null,false);
            ProcessMessages::dispatch(env('SCOPE_REMOVED_FROM_ROLE_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
            return $dataOrError;
        }
        return new DataOrError(['role' => $role], null,false);
    }



    public function addUserToRole(Request $request, string $roleid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        //$groupReq = json_decode($request->getContent(), true);
        $user = $request->user();

        if ($user->tenant_id !== $request->get('tenant_id')){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }

        $role = Role::where('id', trim($roleid))->where('tenant_id', $request->get('tenant_id'))->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $theUser = User::where('id', trim($request->get('user_id')))->where('tenant_id', $request->get('tenant_id'))->first();
        if($theUser === null){
            return new DataOrError(null, [__("Utilisateur inconnu!")],true);
        }

        if ($role->isUserPlayingRole($theUser->id)){
            return new DataOrError(null, [__("L'utilisateur joue deja le role!")],true);
        }

        $currentUsers = json_decode($role->users_playing_role, true);
        $currentUsers[] = $theUser->id;
        $role->users_playing_role = json_encode($currentUsers);
        $role->save();
        $dataOrError = new DataOrError(['role' => $role], null,false);
        ProcessMessages::dispatch(env('USER_ADDED_TO_ROLE_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function retrieveGroupsPlayingRole(Request $request, string $roleid): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();
        $tenant = Tenant::where('id', $user->tenant_id)->where('active', true)->first();
        if($tenant === null){
            return new DataOrError(null, [__("Tenant inconnu!")],true);
        }

        $role = Role::where('id', trim($roleid))->where('tenant_id', $user->tenant_id)->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $groups = json_decode($role->groups_playing_role, true);
        $groupes = [];
        foreach ($groups as $group_id) {
            $group = Group::where('id', trim($group_id))->first();
            $groupes[] = $group;
            $members = json_decode($group->members, true);
            foreach ($members as $member) {
                if($role->isGroupPlayingRole($member)){
                    $groupMember = Group::where('id', trim($member))->where('tenant_id', $user->tenant_id)->first();
                    $groupes[] = $groupMember;
                }
            }
        }
        return new DataOrError(['groups' => $groupes], null,false);
    }

    public function retrieveGroupsNotPlayingRole(Request $request, string $roleid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();
        $tenant = Tenant::where('id', $user->tenant_id)->where('active', true)->first();
        if($tenant === null){
            return new DataOrError(null, [__("Tenant inconnu!")],true);
        }

        $role = Role::where('id', trim($roleid))->where('tenant_id', $user->tenant_id)->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $groups = json_decode($role->groups_playing_role, true);
        $groupes = [];
        foreach ($groups as $group_id) {
            $group = Group::where('id', trim($group_id))->first();
            $groupes[] = $group;
            $members = json_decode($group->members, true);
            foreach ($members as $member) {
                if($role->isGroupPlayingRole($member)){
                    $groupMember = Group::where('id', trim($member))->where('tenant_id', $user->tenant_id)->first();
                    $groupes[] = $groupMember;
                }
            }
        }
        $groupesIds = array_map(function ($g){
            return $g->id;
        },$groupes);

        return new DataOrError(['groups' => Group::whereNotIn('id',$groupesIds)->get()], null,false);
    }

    public function isUserInRole(Request $request, string $roleid, string $userid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();
        $tenant = Tenant::where('id', $user->tenant_id)->where('active', true)->first();
        if($tenant === null){
            return new DataOrError(null, [__("Tenant inconnu!")],true);
        }

        $role = Role::where('id', trim($roleid))->where('tenant_id', $user->tenant_id)->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }

        $theUser = User::where('id', $userid)->where('tenant_id', $user->tenant_id)->first();
        if($theUser === null){
            return new DataOrError(null, [__("User inconnu!")],true);
        }
        return new DataOrError($role->isUserPlayingRole($userid), null,false);
    }

    public function retrieveUsersNotPlayingRole(Request $request, string $roleid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();
        $tenant = Tenant::where('id', $user->tenant_id)->where('active', true)->first();
        if($tenant === null){
            return new DataOrError(null, [__("Tenant inconnu!")],true);
        }

        $role = Role::where('id', trim($roleid))->where('tenant_id', $user->tenant_id)->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $usersInroles = json_decode($role->users_playing_role, true);
        $users = [];
        foreach ($usersInroles as $user_id) {
            $auser = User::where('id', trim($user_id))->first();
            $users[] = $auser;
        }
        // users playing role throughout a group
        $groups = json_decode($role->groups_playing_role, true);
        foreach ($groups as $group_id) {
            $group = Group::where('id', trim($group_id))->where('tenant_id', $user->tenant_id)->first();
            if($group !== null){
                $members = json_decode($group->members, true);
                foreach ($members as $member) {
                    $auser = User::where('id', trim($member))->first();
                    if($auser !== null){
                        $users[] = $auser;
                    }
                }
            }
        }

        $usersIds = array_map(function ($u){
            return $u->id;
        },$users);

        return new DataOrError(['groups' => User::whereNotIn('id',$usersIds)->get()], null,false);
    }

    public function addScopesToRole(Request $request, string $roleid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        //$groupReq = json_decode($request->getContent(), true);
        $user = $request->user();

        if ($user->tenant_id !== $request->get('tenant_id')){
            return new DataOrError(null, [__("Acces interdit!")],true);
        }

        $role = Role::where('id', trim($roleid))->where('tenant_id', $request->get('tenant_id'))->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $scope = Scope::where('key', trim($request->get('scope_key')))->first();
        if($scope === null){
            return new DataOrError(null, [__("Porte inconnu!")],true);
        }

        $roleScopes = json_decode($role->scopes, true);

        if (in_array(trim($request->get('scope_key')),$roleScopes)){
            return new DataOrError(null, [__("Le role possede deja la porte!")],true);
        }
        $roleScopes[] = trim($request->get('scope_key'));
        $role->scopes = json_encode($roleScopes);
        $role->save();
        $dataOrError = new DataOrError(['role' => $role], null,false);
        ProcessMessages::dispatch(env('SCOPE_ADDED_TO_ROLE_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function retrieveAllScopeByRoleId(Request $request, string $roleid)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $user = $request->user();
        $tenant = Tenant::where('id', $user->tenant_id)->where('active', true)->first();
        if($tenant === null){
            return new DataOrError(null, [__("Tenant inconnu!")],true);
        }

        $role = Role::where('id', trim($roleid))->where('tenant_id', $user->tenant_id)->first();
        if($role === null){
            return new DataOrError(null, [__("Role inconnu!")],true);
        }
        $roleScopes = json_decode($role->scopes, true);
        return new DataOrError(['scopes' => $roleScopes], null,false);
    }
}
