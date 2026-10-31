<?php

namespace Tests\Feature;

use App\Domain\Model\AssoLicense;
use App\Domain\Model\Order;
use App\Domain\Model\Payment;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdersAndPaymentsTest extends TestCase
{
    public function test_place_order_succeeds_with_valid_data(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createUser($tenant);
        $orderId = (string) Str::uuid();

        $payload = [
            'id' => $orderId,
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
            'license_type_id' => (string) Str::uuid(),
            'amount' => 100.0,
            'discount' => 10.0,
            'final_amount' => 90.0,
            'status' => 'PLACED',
            'placed_at' => Carbon::now()->toIso8601String(),
            'active' => true,
            'items' => json_encode([
                ['name' => 'STANDARD', 'number_of_days' => 30]
            ]),
        ];

        $this->actingAsUserWithScopes($tenant, []);

        $response = $this->withoutMiddleware('rabbitmq.client')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(202);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
            'status' => 'PLACED',
        ]);
    }

    public function test_place_order_fails_validation(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, []);

        $payload = [
            'status' => 'INVALID_STATUS',
        ];

        $response = $this->withoutMiddleware('rabbitmq.client')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
    }

    public function test_update_order_status_to_paid(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createUser($tenant);
        $orderId = (string) Str::uuid();

        $order = Order::create([
            'id' => $orderId,
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
            'license_type_id' => (string) Str::uuid(),
            'amount' => 50.0,
            'discount' => 0.0,
            'final_amount' => 50.0,
            'placed_at' => Carbon::now(),
            'status' => Order::PLACED,
            'active' => true,
            'items' => json_encode([['name' => 'BASIC', 'number_of_days' => 15]]),
        ]);

        $this->actingAsUserWithScopes($tenant, []);

        $response = $this->withoutMiddleware('rabbitmq.client')
            ->putJson('/api/orders/' . $orderId . '/paid-order', [
                'status' => 'COMPLETED',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $order->refresh();
        $this->assertEquals('COMPLETED', $order->status);
    }

    public function test_update_order_status_to_cancelled(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createUser($tenant);
        $orderId = (string) Str::uuid();

        $order = Order::create([
            'id' => $orderId,
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
            'license_type_id' => (string) Str::uuid(),
            'amount' => 50.0,
            'discount' => 0.0,
            'final_amount' => 50.0,
            'placed_at' => Carbon::now(),
            'status' => Order::PLACED,
            'active' => true,
            'items' => json_encode([['name' => 'BASIC', 'number_of_days' => 15]]),
        ]);

        $this->actingAsUserWithScopes($tenant, []);

        $response = $this->withoutMiddleware('rabbitmq.client')
            ->putJson('/api/orders/' . $orderId . '/cancelled-order', [
                'status' => 'CANCELLED',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'faillure' => 0,
        ]);

        $order->refresh();
        $this->assertEquals('CANCELLED', $order->status);
    }

    public function test_make_service_payment_generates_license(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createUser($tenant);
        $orderId = (string) Str::uuid();

        Order::create([
            'id' => $orderId,
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
            'license_type_id' => (string) Str::uuid(),
            'amount' => 200.0,
            'discount' => 0.0,
            'final_amount' => 200.0,
            'placed_at' => Carbon::now(),
            'status' => Order::PLACED,
            'active' => true,
            'items' => json_encode([
                ['name' => 'PREMIUM', 'number_of_days' => 60]
            ]),
        ]);

        $paymentId = (string) Str::uuid();
        $payload = [
            'id' => $paymentId,
            'tenant_id' => $tenant->id,
            'holder_id' => $merchant->id,
            'payment_service_provider_id' => (string) Str::uuid(),
            'account_used' => json_encode(['account_id' => 'ACC123456']),
            'amount' => 200.0,
            'currency_id' => (string) Str::uuid(),
            'order_number' => $orderId,
            'start_date' => Carbon::now()->toIso8601String(),
            'end_date' => null,
            'status' => Payment::SUCCESS,
            'details' => json_encode(['method' => 'card']),
        ];

        $this->actingAsUserWithScopes($tenant, []);

        $response = $this->withoutMiddleware('rabbitmq.client')
            ->postJson('/api/payments', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 1,
            'failure' => 0,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'order_number' => $orderId,
            'status' => Payment::SUCCESS,
        ]);

        $this->assertDatabaseHas('licenses', [
            'assigned_to' => $merchant->id,
            'tenant_id' => $tenant->id,
            'order_id' => $orderId,
            'active' => true,
        ]);
    }

    public function test_get_valid_licenses_tenant_not_found(): void
    {
        $tenant = $this->createTenant();
        $this->actingAsUserWithScopes($tenant, []);

        $response = $this->getJson('/api/tenants/' . Str::uuid() . '/valid-licences');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => 0,
            'faillure' => 1,
        ]);
    }
}
