<?php

namespace Tests\Feature;

use App\Domain\Model\SuperAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        SuperAdmin::create([
            'id' => (string) Str::uuid(),
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'username' => 'admin',
            'phone_number' => '+237691179154',
            'password' => Hash::make('Password123!'),
            'active' => true,
            'is_super_admin' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
