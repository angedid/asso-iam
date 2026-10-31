<?php

namespace Tests\Unit;

use App\Domain\Model\Group;
use App\Domain\Model\Role;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleAndGroupModelTest extends TestCase
{
    public function test_user_directly_assigned_to_role_is_playing_role(): void
    {
        $tenant = $this->createTenant();
        $userId = (string) Str::uuid();

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Manager',
            'description' => 'Manager role description',
            'groups_playing_role' => json_encode([]),
            'users_playing_role' => json_encode([$userId]),
            'scopes' => json_encode(['SCOPE_MANAGE_ROLE_AND_GROUP']),
            'active' => true,
        ]);

        $this->assertTrue($role->isUserPlayingRole($userId));
        $this->assertFalse($role->isUserPlayingRole((string) Str::uuid()));
    }

    public function test_user_in_group_playing_role_is_playing_role(): void
    {
        $tenant = $this->createTenant();
        $userId = (string) Str::uuid();
        $groupId = (string) Str::uuid();

        Group::create([
            'id' => $groupId,
            'tenant_id' => $tenant->id,
            'name' => 'Finance Team',
            'description' => 'Finance group description',
            'members' => json_encode([$userId]),
            'active' => true,
        ]);

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Auditor',
            'description' => 'Auditor role description',
            'groups_playing_role' => json_encode([$groupId]),
            'users_playing_role' => json_encode([]),
            'scopes' => json_encode([]),
            'active' => true,
        ]);

        $this->assertTrue($role->isUserPlayingRole($userId));
        $this->assertFalse($role->isUserPlayingRole((string) Str::uuid()));
    }

    public function test_user_in_nested_subgroup_playing_role_is_playing_role(): void
    {
        $tenant = $this->createTenant();
        $userId = (string) Str::uuid();
        $subGroupId = (string) Str::uuid();
        $parentGroupId = (string) Str::uuid();

        Group::create([
            'id' => $subGroupId,
            'tenant_id' => $tenant->id,
            'name' => 'Sub Team',
            'description' => 'Sub team group',
            'members' => json_encode([$userId]),
            'active' => true,
        ]);

        Group::create([
            'id' => $parentGroupId,
            'tenant_id' => $tenant->id,
            'name' => 'Parent Team',
            'description' => 'Parent team group',
            'members' => json_encode([$subGroupId]),
            'active' => true,
        ]);

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Executive',
            'description' => 'Executive role description',
            'groups_playing_role' => json_encode([$parentGroupId]),
            'users_playing_role' => json_encode([]),
            'scopes' => json_encode([]),
            'active' => true,
        ]);

        $this->assertTrue($role->isUserPlayingRole($userId));
    }

    public function test_is_group_playing_role_checks_direct_and_nested(): void
    {
        $tenant = $this->createTenant();
        $subGroupId = (string) Str::uuid();
        $parentGroupId = (string) Str::uuid();

        Group::create([
            'id' => $subGroupId,
            'tenant_id' => $tenant->id,
            'name' => 'Sub Team',
            'description' => 'Sub team group',
            'members' => json_encode([]),
            'active' => true,
        ]);

        Group::create([
            'id' => $parentGroupId,
            'tenant_id' => $tenant->id,
            'name' => 'Parent Team',
            'description' => 'Parent team group',
            'members' => json_encode([$subGroupId]),
            'active' => true,
        ]);

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Operations',
            'description' => 'Operations role description',
            'groups_playing_role' => json_encode([$parentGroupId]),
            'users_playing_role' => json_encode([]),
            'scopes' => json_encode([]),
            'active' => true,
        ]);

        $this->assertTrue($role->isGroupPlayingRole($parentGroupId));
        $this->assertTrue($role->isGroupPlayingRole($subGroupId));
        $this->assertFalse($role->isGroupPlayingRole((string) Str::uuid()));
    }

    public function test_group_remove_member_removes_direct_and_nested_members(): void
    {
        $tenant = $this->createTenant();
        $memberId = (string) Str::uuid();
        $subGroupId = (string) Str::uuid();
        $parentGroupId = (string) Str::uuid();

        $subGroup = Group::create([
            'id' => $subGroupId,
            'tenant_id' => $tenant->id,
            'name' => 'Sub Group',
            'description' => 'Sub group description',
            'members' => json_encode([$memberId]),
            'active' => true,
        ]);

        $parentGroup = Group::create([
            'id' => $parentGroupId,
            'tenant_id' => $tenant->id,
            'name' => 'Parent Group',
            'description' => 'Parent group description',
            'members' => json_encode([$subGroupId]),
            'active' => true,
        ]);

        $this->assertTrue($parentGroup->isGroupMember($subGroupId));
        $this->assertTrue($parentGroup->isUserMember($memberId));

        $parentGroup->removeMember($subGroupId);
        $parentGroup->refresh();
        $this->assertFalse($parentGroup->isGroupMember($subGroupId));
    }
}
