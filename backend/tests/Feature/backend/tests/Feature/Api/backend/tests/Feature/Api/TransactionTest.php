<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_transaction(): void
    {
        $user = User::factory()->create([
            'username' => 'transactionuser123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای تراکنش',
            'description' => 'تست تراکنش',
            'owner_id' => $user->id,
            'invite_code' => 'TRN12345',
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'workspace_id' => $workspace->id,
            'name' => 'مشتری تراکنش',
            'phone' => '09121111111',
            'city' => 'کابل',
            'is_pinned' => false,
            'is_active' => true,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/transactions', [
                'workspace_id' => $workspace->id,
                'customer_id' => $customer->id,
                'type' => 'deposit',
                'account_type' => 'receivable',
                'currency' => 'AFN',
                'amount' => 60000,
                'amount_in_words' => 'شصت هزار افغانی',
                'description' => 'تراکنش تست',
                'transaction_date' => '2026-09-15 10:00:00',
            ]);

        $response->assertStatus(201);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'workspace_id',
                'customer_id',
                'user_id',
                'type',
                'account_type',
                'currency',
                'amount',
                'amount_in_words',
                'description',
                'transaction_date',
            ],
        ]);

        $this->assertDatabaseHas('transactions', [
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'account_type' => 'receivable',
            'currency' => 'AFN',
            'amount' => 60000,
        ]);
    }
}
