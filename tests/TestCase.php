<?php

namespace Tests;

use App\Domain\Model\Role;
use App\Domain\Model\SuperAdmin;
use App\Domain\Model\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Mail::fake();
        Storage::fake('sftp');
        Storage::fake('public');
    }

    /**
     * Helper to create a valid tenant for tests.
     */
    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Acme Corporation ' . Str::random(6),
            'city' => 'Douala',
            'location' => 'Akwa',
            'region' => 'Littoral',
            'country' => 'CM',
            'phone_number' => '+237691179154',
            'website' => 'https://acme.example.com',
            'address' => 'Boulevard de la Liberte',
            'active' => true,
            'taxpayer_number' => 'M' . rand(10000000, 99999999) . 'X',
            'number_trade_register' => 'RC' . rand(10000000, 99999999),
            'network' => json_encode([]),
            'private_key_path' => 'home/security/test/private.pem',
            'public_key_path' => 'home/security/test/public.pem',
        ], $attributes));
    }

    /**
     * Ensure the default platform tenant exists.
     */
    protected function ensureDefaultTenant(): Tenant
    {
        $tenant = Tenant::find(Tenant::DEFAULT_TENANT_ID);
        if (!$tenant) {
            $tenant = Tenant::create([
                'id' => Tenant::DEFAULT_TENANT_ID,
                'name' => 'Default Platform Tenant',
                'city' => 'Douala',
                'location' => 'Bonamoussadi',
                'region' => 'Littoral',
                'country' => 'CM',
                'phone_number' => '+237691179154',
                'website' => 'https://stp.example.com',
                'address' => 'Bonamoussadi',
                'active' => true,
                'taxpayer_number' => 'P088112519572E',
                'number_trade_register' => 'RC12345678',
                'network' => json_encode([]),
                'private_key_path' => 'home/security/default/private.pem',
                'public_key_path' => 'home/security/default/public.pem',
            ]);
        }
        return $tenant;
    }

    /**
     * Helper to create a user belonging to a tenant.
     */
    protected function createUser(Tenant $tenant, array $attributes = []): User
    {
        $username = $attributes['local_username'] ?? ('user_' . Str::random(6));
        return User::create(array_merge([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'type' => User::MERCHANT_USER,
            'full_name' => 'Test User',
            'username' => $username . '@' . $tenant->id,
            'local_username' => $username,
            'email' => strtolower($username) . '@example.com',
            'password' => Hash::make('Password123!'),
            'phone_number' => '+237691179154',
            'location' => 'Douala',
            'region' => 'Littoral',
            'country' => 'CM',
            'active' => true,
            'network' => json_encode([]),
        ], $attributes));
    }

    /**
     * Helper to create a role with scopes assigned to users or groups.
     */
    protected function createRoleWithScopes(Tenant $tenant, array $scopes, array $users = [], array $groups = []): Role
    {
        return Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Role_' . Str::random(6),
            'description' => 'Role description with more than 10 characters',
            'groups_playing_role' => json_encode($groups),
            'users_playing_role' => json_encode($users),
            'scopes' => json_encode($scopes),
            'active' => true,
        ]);
    }

    /**
     * Helper to create a super admin.
     */
    protected function createSuperAdmin(array $attributes = []): SuperAdmin
    {
        return SuperAdmin::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Super Administrator',
            'email' => 'superadmin_' . Str::random(5) . '@example.com',
            'username' => 'superadmin_' . Str::random(5),
            'phone_number' => '+237691179154',
            'password' => Hash::make('Password123!'),
            'active' => true,
            'is_super_admin' => true,
        ], $attributes));
    }

    /**
     * Authenticate as a user with given scopes for auth:api requests.
     */
    protected function actingAsUserWithScopes(Tenant $tenant, array $scopes, array $userAttributes = []): User
    {
        $user = $this->createUser($tenant, $userAttributes);
        $this->createRoleWithScopes($tenant, $scopes, [$user->id]);
        Passport::actingAs($user, $scopes);
        return $user;
    }
}
