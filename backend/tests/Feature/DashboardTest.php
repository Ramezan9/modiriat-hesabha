<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_member_can_view_dashboard(): void
    {
        $owner = User::factory()->create();

        $workspace = Workspace::create([
            'name' => 'فضای کاری تست',
            'description' => null,
            'owner_id' => $owner->id,
            'invite_code' => 'TEST1234',
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson(
            "/api/dashboard?workspace_id={$workspace->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customers_count',
                    'balances',
                    'receivables',
                    'payables',
                    'withdrawals',
                    'recent_transactions',
                ],
            ]);
    }

    public function test_non_member_cannot_view_dashboard(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $workspace = Workspace::create([
            'name' => 'فضای کاری تست',
            'description' => null,
            'owner_id' => $owner->id,
            'invite_code' => 'TEST5678',
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson(
            "/api/dashboard?workspace_id={$workspace->id}"
        );

        $response->assertNotFound();
    }

    public function test_dashboard_calculates_balances_and_counts(): void
    {
        $owner = User::factory()->create();

        $workspace = Workspace::create([
            'name' => 'فضای کاری تست',
            'description' => null,
            'owner_id' => $owner->id,
            'invite_code' => 'TEST9012',
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'workspace_id' => $workspace->id,
            'name' => 'مشتری تست',
            'phone' => null,
            'city' => null,
            'profile_photo' => null,
            'is_pinned' => false,
            'is_active' => true,
        ]);

        Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'type' => 'deposit',
            'account_type' => 'receivable',
            'currency' => 'AFN',
            'amount' => 1000,
            'amount_in_words' => 'یک هزار افغانی',
            'description' => 'واریز تست',
            'transaction_date' => now(),
        ]);

        Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'type' => 'withdrawal',
            'account_type' => 'receivable',
            'currency' => 'AFN',
            'amount' => 300,
            'amount_in_words' => 'سیصد افغانی',
            'description' => 'برداشت تست',
            'transaction_date' => now(),
        ]);

        Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'type' => 'deposit',
            'account_type' => 'payable',
            'currency' => 'USD',
            'amount' => 50,
            'amount_in_words' => 'پنجاه دلار',
            'description' => 'واریز تست',
            'transaction_date' => now(),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson(
            "/api/dashboard?workspace_id={$workspace->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customers_count', 1)
            ->assertJsonPath('data.balances.AFN.deposit', 1000)
            ->assertJsonPath('data.balances.AFN.withdrawal', 300)
            ->assertJsonPath('data.balances.AFN.balance', 700)
            ->assertJsonPath('data.receivables.AFN.deposit', 1000)
            ->assertJsonPath('data.receivables.AFN.withdrawal', 300)
            ->assertJsonPath('data.receivables.AFN.balance', 700)
            ->assertJsonPath('data.payables.USD.deposit', 50)
            ->assertJsonPath('data.payables.USD.balance', 50)
            ->assertJsonPath('data.withdrawals.AFN.amount', 300);
    }

    public function test_dashboard_requires_workspace_id(): void
    {
        $owner = User::factory()->create();

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(422);
    }
}
