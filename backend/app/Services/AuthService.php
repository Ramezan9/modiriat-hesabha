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

    /**
     * ثبت‌نام کاربر
     */
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

        $token = $user
            ->createToken('mobile-app')
            ->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * ورود کاربر
     */
    public function login(
        string $username,
        string $password
    ): array {
        $user = $this->users->findByUsername($username);

        if (
            !$user
            || !Hash::check($password, $user->password)
        ) {
            throw ValidationException::withMessages([
                'username' => [
                    'نام کاربری یا رمز عبور نادرست است.'
                ],
            ]);
        }

        $token = $user
            ->createToken('mobile-app')
            ->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * بررسی رمز عبور کاربر
     */
    public function verifyPassword(
        User $user,
        string $password
    ): bool {
        return Hash::check(
            $password,
            $user->password
        );
    }

    /**
     * تغییر رمز عبور
     */
    public function changePassword(
        User $user,
        string $currentPassword,
        string $newPassword
    ): void {
        if (
            !Hash::check(
                $currentPassword,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'رمز عبور فعلی نادرست است.'
                ],
            ]);
        }

        if (
            Hash::check(
                $newPassword,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'new_password' => [
                    'رمز عبور جدید نباید با رمز عبور فعلی یکسان باشد.'
                ],
            ]);
        }

        $user->update([
            'password' => $newPassword,
        ]);
    }

    /**
     * تغییر PIN
     */
    public function changePin(
        User $user,
        string $currentPin,
        string $newPin
    ): void {
        if (!$user->pin) {
            throw ValidationException::withMessages([
                'current_pin' => [
                    'برای این حساب PIN فعالی ثبت نشده است.'
                ],
            ]);
        }

        if (
            !Hash::check(
                $currentPin,
                $user->pin
            )
        ) {
            throw ValidationException::withMessages([
                'current_pin' => [
                    'PIN فعلی نادرست است.'
                ],
            ]);
        }

        if (
            Hash::check(
                $newPin,
                $user->pin
            )
        ) {
            throw ValidationException::withMessages([
                'new_pin' => [
                    'PIN جدید نباید با PIN فعلی یکسان باشد.'
                ],
            ]);
        }

        $user->update([
            'pin' => $newPin,
        ]);
    }

    /**
     * فعال یا غیرفعال کردن ورود با اثر انگشت
     */
    public function setFingerprintEnabled(
        User $user,
        bool $enabled
    ): void {
        $user->update([
            'fingerprint_enabled' => $enabled,
        ]);
    }

    /**
     * خروج از دستگاه فعلی
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * خروج از تمام دستگاه‌ها
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
