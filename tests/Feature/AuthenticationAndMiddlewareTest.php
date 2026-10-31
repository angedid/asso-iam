<?php

namespace Tests\Feature;

use App\Domain\Model\SuperAdmin;
use App\Domain\Model\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthenticationAndMiddlewareTest extends TestCase
{
    public function test_api_user_endpoint_returns_authenticated_user(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        Passport::actingAs($user, []);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_api_user_endpoint_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_requests_to_guarded_routes_return_401(): void
    {
        $this->getJson('/api/roles')->assertStatus(401);
        $this->getJson('/api/groups')->assertStatus(401);
        $this->postJson('/api/users-registrations-invitations', [])->assertStatus(401);
        $this->postJson('/api/users', [])->assertStatus(401);
    }

    public function test_user_verifier_middleware_rejects_unknown_username_on_login(): void
    {
        $tenant = $this->createTenant();

        $response = $this->postJson('/login', [
            'username' => 'nonexistent_user',
            'tenant_id' => $tenant->id,
            'password' => 'SomePassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'error' => 1,
            'success' => 0,
            'errorMessage' => 'Pseudo inconnu.',
        ]);
    }

    public function test_user_verifier_middleware_rejects_inactive_tenant_on_login(): void
    {
        $inactiveTenant = $this->createTenant(['active' => false]);
        $user = $this->createUser($inactiveTenant, [
            'local_username' => 'merchantuser',
        ]);
        $user->username = 'merchantuser@' . $inactiveTenant->id;
        $user->type = User::MERCHANT_USER;
        $user->save();

        $response = $this->postJson('/login', [
            'username' => 'merchantuser',
            'tenant_id' => $inactiveTenant->id,
            'password' => 'SomePassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'error' => 1,
            'success' => 0,
            'errorMessage' => 'Marchand inconnu.',
        ]);
    }

    public function test_setup_app_returns_welcome_when_superadmin_already_exists(): void
    {
        $this->createSuperAdmin();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }
}
