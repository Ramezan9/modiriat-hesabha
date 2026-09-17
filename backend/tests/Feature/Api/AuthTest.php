<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function test_user_can_request_password_reset_code(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset@example.com',
        ]);
    }

    public function test_password_reset_code_is_stored_as_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'hash@example.com',
        ]);

        $code = app(\App\Services\AuthService::class)
            ->createPasswordResetCode($user->email);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        $this->assertNotNull($reset);
        $this->assertNotSame($code, $reset->token);
        $this->assertTrue(
            Hash::check($code, $reset->token)
        );
    }

    public function test_user_can_reset_password_with_valid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'validreset@example.com',
            'password' => '123456',
        ]);

        $oldToken = $user
            ->createToken('old-token')
            ->plainTextToken;

        $code = app(\App\Services\AuthService::class)
            ->createPasswordResetCode($user->email);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJson([
            'message' => 'رمز عبور با موفقیت تغییر کرد. اکنون می‌توانید وارد حساب شوید.',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => '654321',
        ]);

        $loginResponse->assertStatus(200);

        $oldTokenResponse = $this->withToken($oldToken)
            ->getJson('/api/me');

        $oldTokenResponse->assertStatus(401);
    }

    public function test_invalid_password_reset_code_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'invalidcode@example.com',
        ]);

        app(\App\Services\AuthService::class)
            ->createPasswordResetCode($user->email);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '000000',
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'code',
        ]);
    }

    public function test_expired_password_reset_code_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'expired@example.com',
        ]);

        $code = app(\App\Services\AuthService::class)
            ->createPasswordResetCode($user->email);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update([
                'created_at' => now()->subMinutes(11),
            ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'code',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_reset_code_can_only_be_used_once(): void
    {
        $user = User::factory()->create([
            'email' => 'once@example.com',
            'password' => '123456',
        ]);

        $code = app(\App\Services\AuthService::class)
            ->createPasswordResetCode($user->email);

        $firstResponse = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

        $firstResponse->assertStatus(200);

        $secondResponse = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => '111111',
            'password_confirmation' => '111111',
        ]);

        $secondResponse->assertStatus(422);

        $secondResponse->assertJsonValidationErrors([
            'code',
        ]);
    }

    public function test_new_password_cannot_be_same_as_old_password(): void
    {
        $user = User::factory()->create([
            'email' => 'samepassword@example.com',
            'password' => '123456',
        ]);

        $code = app(\App\Services\AuthService::class)
            ->createPasswordResetCode($user->email);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'new_password',
        ]);
    }

    public function test_reset_password_requires_valid_code_format(): void
    {
        $user = User::factory()->create([
            'email' => 'format@example.com',
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '12345',
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'code',
        ]);
    }

    public function test_password_reset_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'confirmation@example.com',
        ]);

        $code = app(\App\Services\AuthService::class)
            ->createPasswordResetCode($user->email);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => '654321',
            'password_confirmation' => '111111',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'password',
        ]);
    }
}
