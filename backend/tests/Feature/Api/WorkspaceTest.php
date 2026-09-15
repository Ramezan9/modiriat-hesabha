<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
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

    public function test_non_member_cannot_view_workspace(): void
    {
        $owner = User::factory()->create([
            'username' => 'owneruser123',
            'password' => '123456',
        ]);

        $otherUser = User::factory()->create([
            'username' => 'otheruser123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای خصوصی',
            'description' => 'تست دسترسی',
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

        $token = $otherUser->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/workspaces/' . $workspace->id);

        $response->assertStatus(404);
    }
}
