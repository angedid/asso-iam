<?php

namespace Tests\Unit;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class DomainServicesTest extends TestCase
{
    public function test_generate_license_key_format(): void
    {
        $key = DomainServices::generateLicenseKey();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{4}-\d{4}$/', $key);
    }

    public function test_tenant_validator_passes_with_valid_data(): void
    {
        $request = new Request([
            'name' => 'Valid Tenant Name',
            'city' => 'Douala',
            'country' => 'CM',
            'phone_number' => '+237691179154',
            'website' => 'https://example.com',
            'address' => 'Valid Address',
        ]);

        $validator = DomainServices::makeTenantsValidators($request);
        $this->assertFalse($validator->fails());
    }

    public function test_tenant_validator_fails_with_invalid_country(): void
    {
        $request = new Request([
            'name' => 'Valid Tenant Name',
            'city' => 'Douala',
            'country' => 'INVALID_CODE',
            'phone_number' => '+237691179154',
        ]);

        $validator = DomainServices::makeTenantsValidators($request);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    public function test_tenant_validator_fails_with_invalid_phone_number(): void
    {
        $request = new Request([
            'name' => 'Valid Tenant Name',
            'city' => 'Douala',
            'country' => 'CM',
            'phone_number' => 'not-a-phone',
        ]);

        $validator = DomainServices::makeTenantsValidators($request);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone_number', $validator->errors()->toArray());
    }

    public function test_role_validator_requires_valid_attributes(): void
    {
        $tenant = $this->createTenant();

        $validRequest = new Request([
            'name' => 'Accountant',
            'description' => 'Accountant role with more than 10 chars',
            'tenant_id' => $tenant->id,
        ]);
        $validator = DomainServices::makeRoleValidator($validRequest);
        $this->assertFalse($validator->fails());

        $invalidRequest = new Request([
            'name' => 'A',
            'description' => 'Short',
            'tenant_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        $validatorInvalid = DomainServices::makeRoleValidator($invalidRequest);
        $this->assertTrue($validatorInvalid->fails());
    }

    public function test_group_validator_validates_name_and_description(): void
    {
        $validRequest = new Request([
            'name' => 'Sales Team',
            'description' => 'Sales team group description',
        ]);
        $validator = DomainServices::makeGroupValidator($validRequest);
        $this->assertFalse($validator->fails());

        $invalidRequest = new Request([
            'name' => '',
            'description' => 'short',
        ]);
        $validatorInvalid = DomainServices::makeGroupValidator($invalidRequest);
        $this->assertTrue($validatorInvalid->fails());
    }

    public function test_retrieve_scopes_aggregates_user_role_scopes(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $role1 = $this->createRoleWithScopes($tenant, ['SCOPE_A', 'SCOPE_B'], [$user->id]);
        $role2 = $this->createRoleWithScopes($tenant, ['SCOPE_C'], [$user->id]);

        $request = Request::create('/test');
        $request->setUserResolver(fn () => $user);

        $scopes = DomainServices::retrieveScopes($request);

        $this->assertContains('SCOPE_A', $scopes);
        $this->assertContains('SCOPE_B', $scopes);
        $this->assertContains('SCOPE_C', $scopes);
    }

    public function test_delete_directory_removes_recursive_files(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir . '/nested', 0777, true);
        file_put_contents($tempDir . '/nested/file.txt', 'test content');

        $this->assertDirectoryExists($tempDir);
        DomainServices::deleteDirectory($tempDir);
        $this->assertDirectoryDoesNotExist($tempDir);
    }
}
