<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceMemberTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkspaceWithManager(
        User $manager
    ): Workspace {
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

    private function createMember(
        Workspace $workspace,
        User $user,
        string $role = 'employee',
        string $status = 'active'
    ): WorkspaceMember {
        return WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function test_member_can_view_workspace_members(): void
    {
        $manager = User::factory()->create();
        $member = User::factory()->create();
        $anotherMember = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);

        $this->createMember($workspace, $member);
        $this->createMember($workspace, $anotherMember);

        Sanctum::actingAs($member);

        $response = $this->getJson(
            "/api/workspaces/{$workspace->id}/members"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_non_member_cannot_view_workspace_members(): void
    {
        $manager = User::factory()->create();
        $nonMember = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);

        Sanctum::actingAs($nonMember);

        $response = $this->getJson(
            "/api/workspaces/{$workspace->id}/members"
        );

        $response->assertNotFound();
    }

    public function test_manager_can_add_member(): void
    {
        $manager = User::factory()->create();
        $newUser = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);

        Sanctum::actingAs($manager);

        $response = $this->postJson(
            "/api/workspaces/{$workspace->id}/members",
            [
                'user_id' => $newUser->id,
                'role' => 'employee',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $newUser->id)
            ->assertJsonPath('data.workspace_id', $workspace->id)
            ->assertJsonPath('data.role', 'employee')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $newUser->id,
            'role' => 'employee',
            'status' => 'active',
        ]);
    }

    public function test_employee_cannot_add_member(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create();
        $newUser = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $this->createMember($workspace, $employee);

        Sanctum::actingAs($employee);

        $response = $this->postJson(
            "/api/workspaces/{$workspace->id}/members",
            [
                'user_id' => $newUser->id,
                'role' => 'employee',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $newUser->id,
        ]);
    }

    public function test_non_member_cannot_add_member(): void
    {
        $manager = User::factory()->create();
        $nonMember = User::factory()->create();
        $newUser = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);

        Sanctum::actingAs($nonMember);

        $response = $this->postJson(
            "/api/workspaces/{$workspace->id}/members",
            [
                'user_id' => $newUser->id,
                'role' => 'employee',
            ]
        );

        $response->assertNotFound();
    }

    public function test_manager_can_update_member(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $member = $this->createMember($workspace, $employee);

        Sanctum::actingAs($manager);

        $response = $this->putJson(
            "/api/workspace-members/{$member->id}",
            [
                'role' => 'manager',
                'status' => 'inactive',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.role', 'manager')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('workspace_members', [
            'id' => $member->id,
            'role' => 'manager',
            'status' => 'inactive',
        ]);
    }

    public function test_employee_cannot_update_member(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create();
        $anotherEmployee = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $this->createMember($workspace, $employee);
        $memberToUpdate = $this->createMember(
            $workspace,
            $anotherEmployee
        );

        Sanctum::actingAs($employee);

        $response = $this->putJson(
            "/api/workspace-members/{$memberToUpdate->id}",
            [
                'role' => 'manager',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('workspace_members', [
            'id' => $memberToUpdate->id,
            'role' => 'employee',
        ]);
    }

    public function test_non_member_cannot_update_member(): void
    {
        $manager = User::factory()->create();
        $nonMember = User::factory()->create();
        $employee = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $member = $this->createMember($workspace, $employee);

        Sanctum::actingAs($nonMember);

        $response = $this->putJson(
            "/api/workspace-members/{$member->id}",
            [
                'role' => 'manager',
            ]
        );

        $response->assertNotFound();
    }

    public function test_manager_can_delete_member(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $member = $this->createMember($workspace, $employee);

        Sanctum::actingAs($manager);

        $response = $this->deleteJson(
            "/api/workspace-members/{$member->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('workspace_members', [
            'id' => $member->id,
        ]);
    }

    public function test_employee_cannot_delete_member(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create();
        $anotherEmployee = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $this->createMember($workspace, $employee);
        $memberToDelete = $this->createMember(
            $workspace,
            $anotherEmployee
        );

        Sanctum::actingAs($employee);

        $response = $this->deleteJson(
            "/api/workspace-members/{$memberToDelete->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('workspace_members', [
            'id' => $memberToDelete->id,
        ]);
    }

    public function test_non_member_cannot_delete_member(): void
    {
        $manager = User::factory()->create();
        $nonMember = User::factory()->create();
        $employee = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $member = $this->createMember($workspace, $employee);

        Sanctum::actingAs($nonMember);

        $response = $this->deleteJson(
            "/api/workspace-members/{$member->id}"
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('workspace_members', [
            'id' => $member->id,
        ]);
    }

    public function test_add_member_requires_valid_user_and_role(): void
    {
        $manager = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);

        Sanctum::actingAs($manager);

        $response = $this->postJson(
            "/api/workspaces/{$workspace->id}/members",
            [
                'user_id' => 999999,
                'role' => 'invalid-role',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'role',
            ]);
    }

    public function test_update_member_rejects_invalid_role_and_status(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create();

        $workspace = $this->createWorkspaceWithManager($manager);
        $member = $this->createMember($workspace, $employee);

        Sanctum::actingAs($manager);

        $response = $this->putJson(
            "/api/workspace-members/{$member->id}",
            [
                'role' => 'invalid-role',
                'status' => 'invalid-status',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
                'status',
            ]);
    }
}
