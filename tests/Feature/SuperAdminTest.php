<?php

namespace Tests\Feature;

use App\Domain\Model\SuperAdmin;
use App\Domain\Model\SuperAdminFirstTimeConnection;
use App\Domain\Model\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    public function test_show_login_form(): void
    {
        $response = $this->get('/super-admins-auth');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login-super-admin');
    }

    public function test_register_super_admin(): void
    {
        $password = 'T8#kL!9vP$mQ@4xZ';
        $username = 'admin_' . Str::random(5);
        $payload = [
            'name' => 'Chief Admin',
            'email' => $username . '@example.com',
            'username' => $username,
            'phone_number' => '+237691179154',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $response = $this->post('/super-admins-register', $payload);

        $response->assertStatus(302);
        $this->assertDatabaseHas('super_admins', [
            'username' => $username,
            'email' => $payload['email'],
        ]);

        $createdAdmin = SuperAdmin::where('username', $username)->first();
        $this->assertDatabaseHas('super_admin_first_time_connections', [
            'id' => $createdAdmin->id,
            'has_been_connected' => false,
        ]);
    }

    public function test_super_admin_first_time_login_prompts_password_change(): void
    {
        $password = 'T8#kL!9vP$mQ@4xZ';
        $superAdmin = $this->createSuperAdmin([
            'password' => Hash::make($password),
        ]);

        SuperAdminFirstTimeConnection::create([
            'id' => $superAdmin->id,
            'has_been_connected' => false,
        ]);

        $response = $this->post('/super-admins-auth', [
            'username' => $superAdmin->username,
            'password' => $password,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('auth.change-pwd');
    }

    public function test_super_admin_subsequent_login_redirects_to_dashboard(): void
    {
        $password = 'T8#kL!9vP$mQ@4xZ';
        $superAdmin = $this->createSuperAdmin([
            'password' => Hash::make($password),
        ]);

        SuperAdminFirstTimeConnection::create([
            'id' => $superAdmin->id,
            'has_been_connected' => true,
        ]);

        $response = $this->post('/super-admins-auth', [
            'username' => $superAdmin->username,
            'password' => $password,
        ]);

        $response->assertRedirect(route('super-admin-dashboard'));
    }

    public function test_super_admin_first_time_password_reset(): void
    {
        $initialPassword = 'T8#kL!9vP$mQ@4xZ';
        $newPassword = 'M5!qR#8vX$wZ@2yB';

        $superAdmin = $this->createSuperAdmin([
            'password' => Hash::make($initialPassword),
        ]);

        $firstTimeRecord = SuperAdminFirstTimeConnection::create([
            'id' => $superAdmin->id,
            'has_been_connected' => false,
        ]);

        $response = $this->post('/super-admin/password-reset-first-time', [
            'userid' => $superAdmin->id,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(route('super-admin-dashboard'));

        $superAdmin->refresh();
        $this->assertTrue(Hash::check($newPassword, $superAdmin->password));

        $firstTimeRecord->refresh();
        $this->assertTrue($firstTimeRecord->has_been_connected);
    }

    public function test_super_admin_tenants_list_and_activate_deactivate(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $tenant = $this->createTenant(['active' => true]);
        $owner = $this->createUser($tenant);
        $tenant->owner = $owner->id;
        $tenant->save();

        // Authenticate guard super_admin
        Auth::guard('super_admin')->login($superAdmin);

        $listResponse = $this->get('/super-admin/tenants');
        $listResponse->assertStatus(200);

        // Deactivate tenant
        $deactivateResponse = $this->post('/super-admin/tenants/' . $tenant->id . '/deactivate');
        $deactivateResponse->assertStatus(302);
        $tenant->refresh();
        $this->assertFalse($tenant->active);

        // Activate tenant
        $activateResponse = $this->post('/super-admin/tenants/' . $tenant->id . '/activate');
        $activateResponse->assertStatus(302);
        $tenant->refresh();
        $this->assertTrue($tenant->active);
    }

    public function test_super_admin_logout(): void
    {
        $superAdmin = $this->createSuperAdmin();
        Auth::guard('super_admin')->login($superAdmin);

        $response = $this->post('/super-admins-auth/logout');
        $response->assertRedirect(route('auth.super-admins.login'));
        $this->assertFalse(Auth::guard('super_admin')->check());
    }
}
