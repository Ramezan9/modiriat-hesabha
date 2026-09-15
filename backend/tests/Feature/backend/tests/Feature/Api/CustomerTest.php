<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_customer(): void
    {
        $user = User::factory()->create([
            'username' => 'customeruser123',
            'password' => '123456',
        ]);

        $workspace = Workspace::create([
            'name' => 'فضای کاری مشتری',
            'description' => 'تست مشتری',
            'owner_id' => $user->id,
            'invite_code' => 'CUS12345',
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/customers', [
                'workspace_id' => $workspace->id,
                'name' => 'مشتری تست',
                'phone' => '09120000000',
                'city' => 'کابل',
                'profile_photo' => null,
                'is_pinned' => true,
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
                'name',
                'phone',
                'city',
                'profile_photo',
                'is_pinned',
                'is_active',
            ],
        ]);

        $this->assertDatabaseHas('customers', [
            'workspace_id' => $workspace->id,
            'name' => 'مشتری تست',
            'phone' => '09120000000',
            'city' => 'کابل',
            'is_pinned' => true,
        ]);
    }
}
