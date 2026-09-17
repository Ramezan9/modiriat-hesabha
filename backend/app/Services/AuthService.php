<?php

namespace App\Services;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const PASSWORD_RESET_CODE_EXPIRATION_MINUTES = 10;

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

        $token = $user
            ->createToken('mobile-app')
            ->plainTextToken;

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

    public function verifyPassword(
        User $user,
        string $password
    ): bool {
        return Hash::check(
            $password,
            $user->password
        );
    }

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

    public function setFingerprintEnabled(
        User $user,
        bool $enabled
    ): void {
        $user->update([
            'fingerprint_enabled' => $enabled,
        ]);
    }

    public function createPasswordResetCode(
        string $email
    ): string {
        $email = strtolower(trim($email));

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [
                    'حسابی با این ایمیل پیدا نشد.'
                ],
            ]);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'email' => $email,
            ],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)->send(
            new PasswordResetCodeMail($code)
        );

        return $code;
    }

    public function verifyPasswordResetCode(
        string $email,
        string $code
    ): bool {
        $email = strtolower(trim($email));

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $reset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$reset || !$reset->created_at) {
            return false;
        }

        $createdAt = Carbon::parse($reset->created_at);

        if (
            now()->greaterThan(
                $createdAt->copy()->addMinutes(
                    self::PASSWORD_RESET_CODE_EXPIRATION_MINUTES
                )
            )
        ) {
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            return false;
        }

        return Hash::check(
            $code,
            $reset->token
        );
    }

    public function resetPassword(
        string $email,
        string $code,
        string $newPassword
    ): void {
        $email = strtolower(trim($email));

        if (
            !$this->verifyPasswordResetCode(
                $email,
                $code
            )
        ) {
            throw ValidationException::withMessages([
                'code' => [
                    'کد بازیابی نادرست یا منقضی شده است.'
                ],
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [
                    'حسابی با این ایمیل پیدا نشد.'
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
                    'رمز عبور جدید نباید با رمز عبور قبلی یکسان باشد.'
                ],
            ]);
        }

        DB::transaction(function () use (
            $user,
            $newPassword,
            $email
        ): void {
            $user->update([
                'password' => $newPassword,
            ]);

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            $user->tokens()->delete();
        });
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
