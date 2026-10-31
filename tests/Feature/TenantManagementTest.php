<?php

namespace Tests\Feature;

use App\Domain\Model\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    public function test_provision_tenant_succeeds_with_valid_data(): void
    {
        $password = 'T8#kL!9vP$mQ@4xZ';
        $payload = [
            'name' => 'Acme Test ' . Str::random(5),
            'city' => 'Douala',
            'location' => 'Bonapriso',
            'region' => 'Littoral',
            'country' => 'CM',
            'phone_number' => '+237691179154',
            'website' => 'https://acme-test.example.com',
            'address' => 'Rue Joss',
            'taxpayer_number' => 'M12345678X',
            'number_trade_register' => 'RC12345678',
            'full_name' => 'John Doe',
            'username' => 'johndoe_' . Str::random(5),
            'password' => $password,
            'password_confirmation' => $password,
            'email' => 'johndoe@example.com',
        ];

        $response = $this->postJson('/api/tenants', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $responseData = $response->json('response.data');
        $this->assertNotNull($responseData['tenant']);
        $this->assertNotNull($responseData['user']);
        $this->assertNotNull($responseData['role_tenant_admin']);
        $this->assertNotNull($responseData['role_tenant_user']);

        $this->assertDatabaseHas('tenants', [
            'name' => $payload['name'],
            'city' => 'Douala',
            'country' => 'CM',
        ]);

        $this->assertDatabaseHas('users', [
            'local_username' => $payload['username'],
            'email' => $payload['email'],
        ]);
    }

    public function test_provision_tenant_fails_validation_when_required_fields_missing(): void
    {
        $payload = [
            'name' => 'Acme',
            // Missing city, country, phone_number, full_name, username, password
        ];

        $response = $this->postJson('/api/tenants', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertNotEmpty($response->json('raison'));
    }

    public function test_provision_tenant_fails_when_name_already_exists_in_same_country(): void
    {
        $tenant = $this->createTenant([
            'name' => 'Duplicate Tenant',
            'country' => 'CM',
        ]);

        $password = 'T8#kL!9vP$mQ@4xZ';
        $payload = [
            'name' => 'Duplicate Tenant',
            'city' => 'Douala',
            'country' => 'CM',
            'phone_number' => '+237691179154',
            'full_name' => 'Jane Doe',
            'username' => 'janedoe_' . Str::random(5),
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $response = $this->postJson('/api/tenants', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertStringContainsString("n'est pas disponible", $response->json('raison.0'));
    }

    public function test_get_tenants_list(): void
    {
        $tenant1 = $this->createTenant(['name' => 'Tenant One']);
        $tenant2 = $this->createTenant(['name' => 'Tenant Two']);

        $response = $this->getJson('/api/tenants');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);
        $tenants = $response->json('response.data.tenants');
        $this->assertGreaterThanOrEqual(2, count($tenants));
    }

    public function test_retrieve_tenant_by_id(): void
    {
        $tenant = $this->createTenant(['name' => 'Specific Tenant']);
        $owner = $this->createUser($tenant, ['full_name' => 'Owner Person']);
        $tenant->owner = $owner->id;
        $tenant->save();

        $response = $this->getJson('/api/tenants/' . $tenant->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);
        $this->assertEquals($tenant->id, $response->json('response.data.tenant.id'));
        $this->assertEquals($owner->id, $response->json('response.data.owner.id'));
    }

    public function test_retrieve_tenant_by_unknown_id_fails(): void
    {
        $response = $this->getJson('/api/tenants/' . Str::uuid());

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertContains('Grossiste inconnu!', $response->json('raison'));
    }

    public function test_activate_tenant_with_permission(): void
    {
        $tenant = $this->createTenant(['active' => false]);
        $user = $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_TENANT']);

        $response = $this->putJson('/api/tenants/' . $tenant->id . '/activate');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $tenant->refresh();
        $this->assertTrue($tenant->active);
    }

    public function test_activate_tenant_forbidden_without_permission(): void
    {
        $tenant = $this->createTenant(['active' => false]);
        $user = $this->actingAsUserWithScopes($tenant, ['SCOPE_READ_ROLE_AND_GROUP']);

        $response = $this->putJson('/api/tenants/' . $tenant->id . '/activate');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
        $this->assertContains("Vous n'etes pas autorise!", $response->json('raison'));
    }

    public function test_deactivate_tenant_with_permission(): void
    {
        $tenant = $this->createTenant(['active' => true]);
        $user = $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_TENANT']);

        $response = $this->putJson('/api/tenants/' . $tenant->id . '/deactivate');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $tenant->refresh();
        $this->assertFalse($tenant->active);
    }

    public function test_update_tenant_with_permission(): void
    {
        $tenant = $this->createTenant(['name' => 'Old Name', 'city' => 'Douala']);
        $user = $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_TENANT']);

        $payload = [
            'name' => 'Updated Tenant Name',
            'city' => 'Yaounde',
            'country' => 'CM',
            'phone_number' => '+237691179154',
            'website' => 'https://updated.example.com',
            'address' => 'Updated Address',
        ];

        $response = $this->putJson('/api/tenants', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $tenant->refresh();
        $this->assertEquals('Updated Tenant Name', $tenant->name);
        $this->assertEquals('Yaounde', $tenant->city);
    }

    public function test_set_and_get_tenant_logo(): void
    {
        $tenant = $this->createTenant();
        $user = $this->actingAsUserWithScopes($tenant, ['SCOPE_MANAGE_TENANT']);

        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->post('/api/tenants/' . $tenant->id . '/logo', [
            'logo' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $tenant->refresh();
        $this->assertNotNull($tenant->logo);

        $getResponse = $this->get('/api/tenants/' . $tenant->id . '/logo');
        $getResponse->assertStatus(200);
    }
}
