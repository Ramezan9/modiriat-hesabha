<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\Transaction;
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
        $response->assertJson(['success' => true]);

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

    public function test_non_member_cannot_create_transaction(): void
    {
        $owner = User::factory()->create([
            'username' => 'transactionowner123',
            'password' => '123456',
        ]);

        $otherUser = User::factory()->create([
            'username' => 'transactionother123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای خصوصی تراکنش',
            'description' => 'تست امنیت تراکنش',
            'owner_id' => $owner->id,
            'invite_code' => 'TRN54321',
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
            'name' => 'مشتری خصوصی',
            'phone' => '09122222222',
            'city' => 'کابل',
            'is_pinned' => false,
            'is_active' => true,
        ]);

        $token = $otherUser->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/transactions', [
                'workspace_id' => $workspace->id,
                'customer_id' => $customer->id,
                'type' => 'deposit',
                'account_type' => 'receivable',
                'currency' => 'AFN',
                'amount' => 50000,
                'transaction_date' => '2026-09-15 11:00:00',
            ]);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('transactions', [
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'amount' => 50000,
        ]);
    }

    public function test_member_can_view_workspace_transactions(): void
    {
        $user = User::factory()->create([
            'username' => 'transactionviewer123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای مشاهده تراکنش',
            'description' => 'تست لیست تراکنش‌ها',
            'owner_id' => $user->id,
            'invite_code' => 'TRN67890',
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
            'name' => 'مشتری لیست تراکنش',
            'phone' => '09123333333',
            'city' => 'کابل',
            'is_pinned' => false,
            'is_active' => true,
        ]);

        Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'account_type' => 'receivable',
            'currency' => 'AFN',
            'amount' => 75000,
            'amount_in_words' => 'هفتاد و پنج هزار افغانی',
            'description' => 'تست نمایش تراکنش',
            'transaction_date' => '2026-09-15 12:00:00',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/transactions?workspace_id=' . $workspace->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonStructure([
            'success',
            'data',
        ]);

        $response->assertJsonFragment([
            'customer_id' => $customer->id,
            'currency' => 'AFN',
            'account_type' => 'receivable',
        ]);

        $response->assertJsonFragment([
            'amount' => '75000.00',
        ]);
    }

    public function test_non_member_cannot_view_workspace_transactions(): void
    {
        $owner = User::factory()->create([
            'username' => 'transactionlistowner123',
            'password' => '123456',
        ]);

        $otherUser = User::factory()->create([
            'username' => 'transactionlistother123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای خصوصی لیست تراکنش',
            'description' => 'تست امنیت لیست تراکنش‌ها',
            'owner_id' => $owner->id,
            'invite_code' => 'TRN24680',
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
            'name' => 'مشتری خصوصی لیست',
            'phone' => '09124444444',
            'city' => 'کابل',
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
            'amount' => 90000,
            'amount_in_words' => 'نود هزار افغانی',
            'description' => 'تراکنش خصوصی',
            'transaction_date' => '2026-09-15 13:00:00',
        ]);

        $token = $otherUser->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/transactions?workspace_id=' . $workspace->id);

        $response->assertStatus(404);

        $response->assertJsonMissing([
            'amount' => '90000.00',
        ]);
    }

    public function test_member_can_view_transaction_details(): void
    {
        $user = User::factory()->create([
            'username' => 'transactionshowuser123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای جزئیات تراکنش',
            'description' => 'تست مشاهده جزئیات',
            'owner_id' => $user->id,
            'invite_code' => 'TRN13579',
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
            'name' => 'مشتری جزئیات',
            'phone' => '09125555555',
            'city' => 'کابل',
            'is_pinned' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'account_type' => 'receivable',
            'currency' => 'AFN',
            'amount' => 120000,
            'amount_in_words' => 'صد و بیست هزار افغانی',
            'description' => 'تست جزئیات تراکنش',
            'transaction_date' => '2026-09-15 14:00:00',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
                'workspace_id' => $workspace->id,
                'customer_id' => $customer->id,
                'type' => 'deposit',
                'account_type' => 'receivable',
                'currency' => 'AFN',
                'amount' => '120000.00',
                'description' => 'تست جزئیات تراکنش',
            ],
        ]);

        $response->assertJsonPath(
            'data.customer.id',
            $customer->id
        );

        $response->assertJsonPath(
            'data.customer.name',
            'مشتری جزئیات'
        );
    }

    public function test_non_member_cannot_view_transaction_details(): void
    {
        $owner = User::factory()->create([
            'username' => 'transactiondetailsowner123',
            'password' => '123456',
        ]);

        $otherUser = User::factory()->create([
            'username' => 'transactiondetailsother123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای خصوصی جزئیات',
            'description' => 'تست امنیت جزئیات تراکنش',
            'owner_id' => $owner->id,
            'invite_code' => 'TRN97531',
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
            'name' => 'مشتری خصوصی جزئیات',
            'phone' => '09126666666',
            'city' => 'کابل',
            'is_pinned' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'type' => 'deposit',
            'account_type' => 'receivable',
            'currency' => 'AFN',
            'amount' => 150000,
            'amount_in_words' => 'صد و پنجاه هزار افغانی',
            'description' => 'اطلاعات مالی خصوصی',
            'transaction_date' => '2026-09-15 15:00:00',
        ]);

        $token = $otherUser->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(404);

        $response->assertJsonMissing([
            'amount' => '150000.00',
        ]);
    }
}
