<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkspaceWithManager(User $manager): Workspace
    {
        $workspace = Workspace::create([
            'name' => 'فضای کاری تست',
            'description' => null,
            'owner_id' => $manager->id,
            'invite_code' => fake()->unique()->regexify('[A-Z0-9]{8}'),
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $manager->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        return $workspace;
    }

    private function createTransaction(
        Workspace $workspace,
        User $user
    ): Transaction {
        $customer = Customer::create([
            'workspace_id' => $workspace->id,
            'name' => 'مشتری تست',
            'phone' => null,
            'city' => null,
            'profile_photo' => null,
            'is_pinned' => false,
            'is_active' => true,
        ]);

        return Transaction::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'account_type' => 'receivable',
            'currency' => 'AFN',
            'amount' => 1000,
            'amount_in_words' => 'یک هزار افغانی',
            'description' => 'تراکنش تست',
            'transaction_date' => now(),
        ]);
    }

    private function createMember(
        Workspace $workspace,
        User $user,
        string $role = 'employee'
    ): WorkspaceMember {
        return WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_member_can_view_transaction_receipts(): void
    {
        $manager = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $this->createMember($workspace, $member);

        $transaction = $this->createTransaction($workspace, $manager);

        $receipt = Receipt::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/test.jpg',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        Sanctum::actingAs($member);

        $response = $this->getJson(
            "/api/transactions/{$transaction->id}/receipts"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $receipt->id)
            ->assertJsonPath('data.0.file_path', 'receipts/test.jpg');
    }

    public function test_non_member_cannot_view_transaction_receipts(): void
    {
        $manager = User::factory()->create();
        $nonMember = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $transaction = $this->createTransaction($workspace, $manager);

        Receipt::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/test.jpg',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        Sanctum::actingAs($nonMember);

        $response = $this->getJson(
            "/api/transactions/{$transaction->id}/receipts"
        );

        $response->assertNotFound();
    }

    public function test_manager_can_store_receipt(): void
    {
        $manager = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $transaction = $this->createTransaction($workspace, $manager);

        Sanctum::actingAs($manager);

        $response = $this->postJson(
            "/api/transactions/{$transaction->id}/receipts",
            [
                'file_path' => 'receipts/new-receipt.jpg',
                'file_name' => 'new-receipt.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 2048,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.file_path', 'receipts/new-receipt.jpg');

        $this->assertDatabaseHas('receipts', [
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/new-receipt.jpg',
            'file_name' => 'new-receipt.jpg',
        ]);
    }

    public function test_member_cannot_store_receipt(): void
    {
        $manager = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $this->createMember($workspace, $member);

        $transaction = $this->createTransaction($workspace, $manager);

        Sanctum::actingAs($member);

        $response = $this->postJson(
            "/api/transactions/{$transaction->id}/receipts",
            [
                'file_path' => 'receipts/member-receipt.jpg',
                'file_name' => 'member-receipt.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 1024,
            ]
        );

        $response->assertForbidden();
    }

    public function test_receipt_store_requires_file_path(): void
    {
        $manager = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $transaction = $this->createTransaction($workspace, $manager);

        Sanctum::actingAs($manager);

        $response = $this->postJson(
            "/api/transactions/{$transaction->id}/receipts",
            [
                'file_name' => 'receipt.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 1024,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file_path']);
    }

    public function test_member_can_view_receipt_details(): void
    {
        $manager = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $this->createMember($workspace, $member);

        $transaction = $this->createTransaction($workspace, $manager);

        $receipt = Receipt::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/details.jpg',
            'file_name' => 'details.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 4096,
        ]);

        Sanctum::actingAs($member);

        $response = $this->getJson(
            "/api/receipts/{$receipt->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $receipt->id)
            ->assertJsonPath('data.file_path', 'receipts/details.jpg');
    }

    public function test_non_member_cannot_view_receipt_details(): void
    {
        $manager = User::factory()->create();
        $nonMember = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $transaction = $this->createTransaction($workspace, $manager);

        $receipt = Receipt::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/private.jpg',
            'file_name' => 'private.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
        ]);

        Sanctum::actingAs($nonMember);

        $response = $this->getJson(
            "/api/receipts/{$receipt->id}"
        );

        $response->assertNotFound();
    }

    public function test_manager_can_delete_receipt(): void
    {
        $manager = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $transaction = $this->createTransaction($workspace, $manager);

        $receipt = Receipt::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/delete.jpg',
            'file_name' => 'delete.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        Sanctum::actingAs($manager);

        $response = $this->deleteJson(
            "/api/receipts/{$receipt->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('receipts', [
            'id' => $receipt->id,
        ]);
    }

    public function test_member_cannot_delete_receipt(): void
    {
        $manager = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $this->createMember($workspace, $member);

        $transaction = $this->createTransaction($workspace, $manager);

        $receipt = Receipt::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/member-delete.jpg',
            'file_name' => 'member-delete.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        Sanctum::actingAs($member);

        $response = $this->deleteJson(
            "/api/receipts/{$receipt->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
        ]);
    }

    public function test_non_member_cannot_delete_receipt(): void
    {
        $manager = User::factory()->create();
        $nonMember = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $transaction = $this->createTransaction($workspace, $manager);

        $receipt = Receipt::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'receipts/non-member-delete.jpg',
            'file_name' => 'non-member-delete.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        Sanctum::actingAs($nonMember);

        $response = $this->deleteJson(
            "/api/receipts/{$receipt->id}"
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
        ]);
    }
}
