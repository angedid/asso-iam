<?php

namespace Tests\Feature;

use App\Domain\Model\Group;
use App\Domain\Model\Role;
use App\Domain\Model\Scope;
use App\Jobs\ProcessMessages;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleAndGroupManagementTest extends TestCase
{
    public function test_create_role_succeeds_with_proper_scope(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_ROLE_AND_GROUP']);

        $payload = [
            'name' => 'Inventory Manager',
            'description' => 'Role for managing warehouse items and stocks',
            'tenant_id' => $tenant->id,
        ];

        $response = $this->postJson('/api/roles', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Inventory Manager',
            'tenant_id' => $tenant->id,
        ]);

        Queue::assertPushed(ProcessMessages::class);
    }

    public function test_create_role_forbidden_without_proper_scope(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_READ_ROLE_AND_GROUP']);

        $payload = [
            'name' => 'Unauthorized Role',
            'description' => 'Should fail due to missing scope',
            'tenant_id' => $tenant->id,
        ];

        $response = $this->postJson('/api/roles', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertContains("Vous n'etes pas autorise!", $response->json('raison'));
    }

    public function test_create_role_fails_when_duplicate_name_for_same_tenant(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_ROLE_AND_GROUP']);

        $this->createRoleWithScopes($tenant, [], [], []);
        $roleName = 'Existing Unique Role';
        Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => $roleName,
            'description' => 'Existing description with more than 10 chars',
            'groups_playing_role' => json_encode([]),
            'users_playing_role' => json_encode([]),
            'scopes' => json_encode([]),
            'active' => true,
        ]);

        $payload = [
            'name' => $roleName,
            'description' => 'Trying to create duplicate role',
            'tenant_id' => $tenant->id,
        ];

        $response = $this->postJson('/api/roles', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertContains('Nom deja utilise pour cette entreprise.', $response->json('raison'));
    }

    public function test_retrieve_all_roles_for_tenant(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_READ_ROLE_AND_GROUP']);

        $response = $this->getJson('/api/roles');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);
        $roles = $response->json('response.data.roles');
        $this->assertIsArray($roles);
    }

    public function test_add_and_remove_user_in_role(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_ROLE_AND_GROUP']);
        $targetUser = $this->createUser($tenant);

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Quality Inspector',
            'description' => 'Quality inspector description long enough',
            'groups_playing_role' => json_encode([]),
            'users_playing_role' => json_encode([]),
            'scopes' => json_encode([]),
            'active' => true,
        ]);

        // Add user to role
        $addPayload = [
            'tenant_id' => $tenant->id,
            'user_id' => $targetUser->id,
        ];
        $addResponse = $this->postJson('/api/roles/' . $role->id . '/users', $addPayload);
        $addResponse->assertStatus(200);
        $addResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $role->refresh();
        $this->assertTrue($role->isUserPlayingRole($targetUser->id));

        // Attempting to add again fails
        $addAgainResponse = $this->postJson('/api/roles/' . $role->id . '/users', $addPayload);
        $addAgainResponse->assertStatus(200);
        $addAgainResponse->assertJson(['success' => 0, 'faillure' => 1]);

        // Remove user from role
        $removeResponse = $this->deleteJson('/api/roles/' . $role->id . '/users', $addPayload);
        $removeResponse->assertStatus(200);
        $removeResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $role->refresh();
        $this->assertFalse($role->isUserPlayingRole($targetUser->id));
    }

    public function test_is_user_in_role_endpoint(): void
    {
        $tenant = $this->createTenant();
        $targetUser = $this->createUser($tenant);
        $role = $this->createRoleWithScopes($tenant, ['SCOPE_A'], [$targetUser->id]);

        $this->actingAsUserWithScopes($tenant, ['SCOPE_READ_ROLE_AND_GROUP']);

        $response = $this->getJson('/api/roles/' . $role->id . '/users/' . $targetUser->id . '/is-in-role');
        $response->assertStatus(200);
        $this->assertTrue($response->json('response.data'));

        $otherTenantUser = $this->createUser($tenant);
        $responseOther = $this->getJson('/api/roles/' . $role->id . '/users/' . $otherTenantUser->id . '/is-in-role');
        $responseOther->assertStatus(200);
        $this->assertFalse($responseOther->json('response.data'));
    }

    public function test_create_and_retrieve_groups(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_ROLE_AND_GROUP']);

        $payload = [
            'name' => 'Logistics Team',
            'description' => 'Logistics department operations team',
            'tenant_id' => $tenant->id,
        ];

        $createResponse = $this->postJson('/api/groups', $payload);
        $createResponse->assertStatus(200);
        $createResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $this->assertDatabaseHas('groups', [
            'name' => 'Logistics Team',
            'tenant_id' => $tenant->id,
        ]);

        $listResponse = $this->getJson('/api/groups');
        $listResponse->assertStatus(200);
        $groups = $listResponse->json('response.data.groups');
        $this->assertGreaterThanOrEqual(1, count($groups));
    }

    public function test_add_and_remove_member_in_group(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_ROLE_AND_GROUP']);
        $memberUser = $this->createUser($tenant);

        $group = Group::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Billing Squad',
            'description' => 'Billing squad team description',
            'members' => json_encode([]),
            'active' => true,
        ]);

        $memberPayload = [
            'tenant_id' => $tenant->id,
            'member_id' => $memberUser->id,
        ];

        // Add member
        $addResponse = $this->postJson('/api/groups/' . $group->id . '/members', $memberPayload);
        $addResponse->assertStatus(200);
        $addResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $group->refresh();
        $this->assertTrue($group->isUserMember($memberUser->id));

        // Remove member
        $removeResponse = $this->deleteJson('/api/groups/' . $group->id . '/members', $memberPayload);
        $removeResponse->assertStatus(200);
        $removeResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $group->refresh();
        $this->assertFalse($group->isUserMember($memberUser->id));
    }

    public function test_add_and_remove_group_in_role(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_ROLE_AND_GROUP']);

        $group = Group::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Dispatch Team',
            'description' => 'Dispatch group description',
            'members' => json_encode([]),
            'active' => true,
        ]);

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Dispatcher',
            'description' => 'Dispatcher role description',
            'groups_playing_role' => json_encode([]),
            'users_playing_role' => json_encode([]),
            'scopes' => json_encode([]),
            'active' => true,
        ]);

        $groupPayload = [
            'tenant_id' => $tenant->id,
            'group_id' => $group->id,
        ];

        // Add group to role
        $addResponse = $this->postJson('/api/roles/' . $role->id . '/groups', $groupPayload);
        $addResponse->assertStatus(200);
        $addResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $role->refresh();
        $this->assertTrue($role->isGroupPlayingRole($group->id));

        // Remove group from role
        $removeResponse = $this->deleteJson('/api/roles/' . $role->id . '/groups', $groupPayload);
        $removeResponse->assertStatus(200);
        $removeResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $role->refresh();
        $this->assertFalse($role->isGroupPlayingRole($group->id));
    }

    public function test_retrieve_scopes_endpoint(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, ['SCOPE_READ_ROLE_AND_GROUP']);

        $response = $this->getJson('/api/scopes');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);
        $this->assertIsArray($response->json('response.data.scopes'));
    }
}
