<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'کاربر تست',
            'username' => 'testuser123',
            'email' => 'test@example.com',
            'phone' => '09120000000',
            'password' => '123456',
            'pin' => '1234',
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser123',
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'username' => 'loginuser123',
            'password' => '123456',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'loginuser123',
            'password' => '123456',
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token',
            ],
        ]);

        $this->assertNotEmpty(
            $response->json('data.token')
        );
    }

    public function test_authenticated_user_can_view_me(): void
    {
        $user = User::factory()->create([
            'username' => 'meuser123',
            'password' => '123456',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/me');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => 'meuser123',
                ],
            ],
        ]);
    }
}
