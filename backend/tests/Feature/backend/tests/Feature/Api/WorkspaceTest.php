<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_workspace(): void
    {
        $user = User::factory()->create([
            'username' => 'workspaceuser123',
            'password' => '123456',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/workspaces', [
                'name' => 'حساب‌های من',
                'description' => 'فضای کاری تست',
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
                'name',
                'description',
                'owner_id',
                'invite_code',
                'is_active',
            ],
        ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'حساب‌های من',
            'owner_id' => $user->id,
        ]);

        $this->assertDatabaseHas('workspace_members', [
            'user_id' => $user->id,
            'role' => 'manager',
            'status' => 'active',
        ]);
    }
}
