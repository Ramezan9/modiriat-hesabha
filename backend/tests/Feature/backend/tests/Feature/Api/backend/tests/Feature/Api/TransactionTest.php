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

    public function test_manager_can_update_transaction(): void
    {
        $user = User::factory()->create([
            'username' => 'transactionupdateuser123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای ویرایش تراکنش',
            'description' => 'تست ویرایش تراکنش',
            'owner_id' => $user->id,
            'invite_code' => 'TRN86420',
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
            'name' => 'مشتری ویرایش',
            'phone' => '09127777777',
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
            'amount' => 60000,
            'amount_in_words' => 'شصت هزار افغانی',
            'description' => 'توضیح قبلی',
            'transaction_date' => '2026-09-15 16:00:00',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->putJson('/api/transactions/' . $transaction->id, [
                'amount' => 85000,
                'amount_in_words' => 'هشتاد و پنج هزار افغانی',
                'description' => 'توضیح جدید',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 85000,
            'amount_in_words' => 'هشتاد و پنج هزار افغانی',
            'description' => 'توضیح جدید',
        ]);
    }

    public function test_member_cannot_update_transaction(): void
    {
        $owner = User::factory()->create([
            'username' => 'transactionupdateowner123',
            'password' => '123456',
        ]);

        $member = User::factory()->create([
            'username' => 'transactionupdatemember123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای محدود ویرایش',
            'description' => 'تست محدودیت ویرایش تراکنش',
            'owner_id' => $owner->id,
            'invite_code' => 'TRN11223',
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'employee',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'workspace_id' => $workspace->id,
            'name' => 'مشتری محدود',
            'phone' => '09128888888',
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
            'amount' => 70000,
            'amount_in_words' => 'هفتاد هزار افغانی',
            'description' => 'اطلاعات اصلی',
            'transaction_date' => '2026-09-15 17:00:00',
        ]);

        $token = $member->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->putJson('/api/transactions/' . $transaction->id, [
                'amount' => 999999,
                'description' => 'تغییر غیرمجاز',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 70000,
            'description' => 'اطلاعات اصلی',
        ]);
    }

    public function test_manager_can_delete_transaction(): void
    {
        $user = User::factory()->create([
            'username' => 'transactiondeleteuser123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای حذف تراکنش',
            'description' => 'تست حذف تراکنش',
            'owner_id' => $user->id,
            'invite_code' => 'TRN33445',
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
            'name' => 'مشتری حذف',
            'phone' => '09129999999',
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
            'amount' => 50000,
            'amount_in_words' => 'پنجاه هزار افغانی',
            'description' => 'تراکنش قابل حذف',
            'transaction_date' => '2026-09-15 18:00:00',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_member_cannot_delete_transaction(): void
    {
        $owner = User::factory()->create([
            'username' => 'transactiondeleteowner123',
            'password' => '123456',
        ]);

        $member = User::factory()->create([
            'username' => 'transactiondeletemember123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای محدود حذف',
            'description' => 'تست محدودیت حذف تراکنش',
            'owner_id' => $owner->id,
            'invite_code' => 'TRN55667',
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'employee',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'workspace_id' => $workspace->id,
            'name' => 'مشتری حذف محدود',
            'phone' => '09120000000',
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
            'amount' => 80000,
            'amount_in_words' => 'هشتاد هزار افغانی',
            'description' => 'تراکنش محافظت شده',
            'transaction_date' => '2026-09-15 19:00:00',
        ]);

        $token = $member->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 80000,
        ]);
    }

    public function test_non_member_cannot_delete_transaction(): void
    {
        $owner = User::factory()->create([
            'username' => 'transactiondeletenonowner123',
            'password' => '123456',
        ]);

        $otherUser = User::factory()->create([
            'username' => 'transactiondeletenonmember123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای خصوصی حذف',
            'description' => 'تست امنیت حذف تراکنش',
            'owner_id' => $owner->id,
            'invite_code' => 'TRN77889',
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
            'name' => 'مشتری خصوصی حذف',
            'phone' => '09121112222',
            'city' => 'کابل',
            'is_pinned' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'type' => 'deposit',
            'account_type' => 'payable',
            'currency' => 'USD',
            'amount' => 1000,
            'amount_in_words' => 'هزار دلار',
            'description' => 'تراکنش خصوصی',
            'transaction_date' => '2026-09-15 20:00:00',
        ]);

        $token = $otherUser->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 1000,
        ]);
    }
}
