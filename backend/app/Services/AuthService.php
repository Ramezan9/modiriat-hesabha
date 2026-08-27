<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function register(array $data): array
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'pin' => $data['pin'] ?? null,
            'fingerprint_enabled' => false,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(
        string $username,
        string $password
    ): array {
        $user = $this->users->findByUsername($username);

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['نام کاربری یا رمز عبور نادرست است.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
