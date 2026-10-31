<?php

namespace Tests\Feature;

use App\Constances;
use App\Domain\Model\PasswordResetInvitation;
use App\Domain\Model\Tenant;
use App\Domain\Model\UserRegistrationInvitation;
use App\Jobs\ProcessMessages;
use App\Jobs\ProcessSendEMailPasswordResetInvitationJob;
use App\Jobs\ProcessSendEMailUserInvitationJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_invite_user_succeeds_for_authenticated_tenant_member(): void
    {
        $tenant = $this->createTenant();
        $inviter = $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_COLLABORATORS']);

        $payload = [
            'full_name' => 'Invited Colleague',
            'email' => 'colleague@example.com',
            'tenant_id' => $tenant->id,
            'invited_by' => $inviter->id,
            'phone_number' => '+237691179154',
        ];

        $response = $this->postJson('/api/users-registrations-invitations', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $this->assertDatabaseHas('users_registration_invitations', [
            'email' => 'colleague@example.com',
            'tenant_id' => $tenant->id,
            'invited_by' => $inviter->id,
            'state' => Constances::PENDING,
        ]);

        Queue::assertPushed(ProcessSendEMailUserInvitationJob::class);
    }

    public function test_invite_user_fails_when_inviter_belongs_to_different_tenant(): void
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $inviter = $this->actingAsUserWithScopes($tenant1, []);

        $payload = [
            'full_name' => 'Invited User',
            'email' => 'user@example.com',
            'tenant_id' => $tenant2->id,
            'invited_by' => $inviter->id,
        ];

        $response = $this->postJson('/api/users-registrations-invitations', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertContains('User not in tenant', $response->json('raison'));
    }

    public function test_register_user_with_valid_invitation(): void
    {
        $tenant = $this->createTenant();
        $inviter = $this->createUser($tenant);

        $invitation = UserRegistrationInvitation::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'full_name' => 'New Staff',
            'email' => 'staff@example.com',
            'invited_by' => $inviter->id,
            'invited_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(7),
            'active' => true,
            'state' => Constances::PENDING,
            'used' => false,
            'url' => 'https://example.com/invitation',
        ]);

        $this->actingAsUserWithScopes($tenant, []);

        $password = 'T8#kL!9vP$mQ@4xZ';
        $payload = [
            'full_name' => 'New Staff',
            'username' => 'staff_' . Str::random(5),
            'phone_number' => '+237691179154',
            'password' => $password,
            'password_confirmation' => $password,
            'invitation_id' => $invitation->id,
            'country' => 'CM',
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'local_username' => $payload['username'],
            'email' => 'staff@example.com',
            'tenant_id' => $tenant->id,
        ]);

        Queue::assertPushed(ProcessMessages::class);
    }

    public function test_register_user_fails_with_expired_invitation(): void
    {
        $tenant = $this->createTenant();
        $inviter = $this->createUser($tenant);

        $invitation = UserRegistrationInvitation::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'full_name' => 'Expired Staff',
            'email' => 'expired@example.com',
            'invited_by' => $inviter->id,
            'invited_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->subDays(2),
            'active' => true,
            'state' => Constances::PENDING,
            'used' => false,
            'url' => 'https://example.com/invitation',
        ]);

        $this->actingAsUserWithScopes($tenant, []);

        $password = 'T8#kL!9vP$mQ@4xZ';
        $payload = [
            'full_name' => 'Expired Staff',
            'username' => 'expired_' . Str::random(5),
            'phone_number' => '+237691179154',
            'password' => $password,
            'password_confirmation' => $password,
            'invitation_id' => $invitation->id,
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertContains('Invitation expiree', $response->json('raison'));
    }

    public function test_register_seller(): void
    {
        $this->ensureDefaultTenant();

        $password = 'T8#kL!9vP$mQ@4xZ';
        $payload = [
            'full_name' => 'Seller Person',
            'username' => 'seller_' . Str::random(5),
            'phone_number' => '+237691179154',
            'password' => $password,
            'password_confirmation' => $password,
            'email' => 'seller_' . Str::random(5) . '@example.com',
            'country' => 'CM',
        ];

        $response = $this->postJson('/api/sellers', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $this->assertDatabaseHas('users', [
            'local_username' => $payload['username'],
            'type' => 'SELLER_USER',
            'tenant_id' => Tenant::DEFAULT_TENANT_ID,
        ]);

        Queue::assertPushed(ProcessMessages::class);
    }

    public function test_is_user_exists_endpoint(): void
    {
        $tenant = $this->createTenant();
        $user = $this->actingAsUserWithScopes($tenant, []);

        $responseExisting = $this->getJson('/api/users/' . $user->id . '/exist');
        $responseExisting->assertStatus(200);
        $this->assertTrue($responseExisting->json('response.data'));

        $responseNonExisting = $this->getJson('/api/users/' . Str::uuid() . '/exist');
        $responseNonExisting->assertStatus(200);
        $this->assertFalse($responseNonExisting->json('response.data'));
    }

    public function test_update_user(): void
    {
        $tenant = $this->createTenant();
        $user = $this->actingAsUserWithScopes($tenant, []);

        $payload = [
            'full_name' => 'Updated User Name',
            'phone_number' => '+237691179154',
            'location' => 'Bonanjo',
            'region' => 'Littoral',
            'country' => 'CM',
        ];

        $response = $this->putJson('/api/users', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $user->refresh();
        $this->assertEquals('Updated User Name', $user->full_name);
        $this->assertEquals('Bonanjo', $user->location);
    }

    public function test_activate_and_deactivate_user(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_TENANT']);
        $targetUser = $this->createUser($tenant, ['active' => true]);

        // Deactivate
        $deactivateResponse = $this->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id . '/deactivate');
        $deactivateResponse->assertStatus(200);
        $deactivateResponse->assertJson(['success' => 1, 'faillure' => 0]);
        $targetUser->refresh();
        $this->assertFalse($targetUser->active);

        // Activate
        $activateResponse = $this->putJson('/api/tenants/' . $tenant->id . '/users/' . $targetUser->id . '/activate');
        $activateResponse->assertStatus(200);
        $activateResponse->assertJson(['success' => 1, 'faillure' => 0]);
        $targetUser->refresh();
        $this->assertTrue($targetUser->active);
    }

    public function test_password_reset_flow(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [
            'email' => 'resetme@example.com',
            'local_username' => 'resetuser',
        ]);
        $user->username = 'resetuser';
        $user->save();

        // 1. Request reset
        $requestPayload = [
            'tenant_id' => $tenant->id,
            'username' => 'resetuser',
            'email' => 'resetme@example.com',
        ];

        $response = $this->postJson('/api/password-reset-request', $requestPayload);
        $response->assertStatus(200);
        $response->assertJson(['success' => 1, 'faillure' => 0]);

        $invitation = PasswordResetInvitation::where('username', 'resetuser')->first();
        $this->assertNotNull($invitation);

        Queue::assertPushed(ProcessSendEMailPasswordResetInvitationJob::class);

        // 2. Perform reset
        $newPassword = 'T8#kL!9vP$mQ@4xZ';
        $resetPayload = [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];

        $resetResponse = $this->postJson('/api/password-reset-request/' . $invitation->id, $resetPayload);
        $resetResponse->assertStatus(200);
        $resetResponse->assertJson(['success' => 1, 'faillure' => 0]);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    public function test_change_password_with_valid_current_password(): void
    {
        $tenant = $this->createTenant();
        $initialPassword = 'T8#kL!9vP$mQ@4xZ';
        $newPassword = 'M5!qR#8vX$wZ@2yB';

        $user = $this->actingAsUserWithScopes($tenant, [], [
            'password' => Hash::make($initialPassword),
        ]);

        $payload = [
            'current_password' => $initialPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];

        $response = $this->postJson('/api/tenants/' . $tenant->id . '/users/' . $user->id . '/change-password', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    public function test_change_password_fails_with_incorrect_current_password(): void
    {
        $tenant = $this->createTenant();
        $user = $this->actingAsUserWithScopes($tenant, [], [
            'password' => Hash::make('T8#kL!9vP$mQ@4xZ'),
        ]);

        $payload = [
            'current_password' => 'WrongCurrent123!@#',
            'password' => 'M5!qR#8vX$wZ@2yB',
            'password_confirmation' => 'M5!qR#8vX$wZ@2yB',
        ];

        $response = $this->postJson('/api/tenants/' . $tenant->id . '/users/' . $user->id . '/change-password', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertContains('Mot de passe courant incorrect', $response->json('raison'));
    }

    public function test_set_and_get_user_photo(): void
    {
        $tenant = $this->createTenant();
        $user = $this->actingAsUserWithScopes($tenant, []);

        $file = UploadedFile::fake()->image('avatar.jpg', 120, 120);

        $response = $this->post('/api/users/' . $user->id . '/photo', [
            'photo' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => 1, 'faillure' => 0]);

        $user->refresh();
        $this->assertNotNull($user->photo);

        $getResponse = $this->get('/api/users/' . $user->id . '/photo');
        $getResponse->assertStatus(200);
    }
}
