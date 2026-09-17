<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const PASSWORD_RESET_CODE_EXPIRATION_MINUTES = 10;

    private const PASSWORD_RESET_REQUEST_MAX_ATTEMPTS = 5;

    private const PASSWORD_RESET_VERIFY_MAX_ATTEMPTS = 10;

    private const PASSWORD_RESET_RATE_LIMIT_SECONDS = 600;

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
     * ایجاد کد ۶ رقمی بازیابی رمز عبور
     *
     * برای جلوگیری از افشای وجود حساب،
     * در صورت نبودن ایمیل نیز همان جریان پاسخ را حفظ می‌کنیم.
     *
     * کد خام فقط برای ارسال ایمیل استفاده می‌شود
     * و هرگز به صورت خام در دیتابیس ذخیره نمی‌شود.
     */
    public function createPasswordResetCode(
        string $email
    ): string {
        $email = strtolower(trim($email));

        $rateLimitKey = $this->passwordResetRequestRateLimitKey(
            $email
        );

        if (
            RateLimiter::tooManyAttempts(
                $rateLimitKey,
                self::PASSWORD_RESET_REQUEST_MAX_ATTEMPTS
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'تعداد درخواست‌های بازیابی بیش از حد مجاز است. لطفاً بعداً دوباره تلاش کنید.'
                ],
            ]);
        }

        RateLimiter::hit(
            $rateLimitKey,
            self::PASSWORD_RESET_RATE_LIMIT_SECONDS
        );

        $code = (string) random_int(100000, 999999);

        $user = User::where('email', $email)->first();

        if ($user) {
            DB::table('password_reset_tokens')->updateOrInsert(
                [
                    'email' => $email,
                ],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );
        }

        return $code;
    }

    /**
     * بررسی کد ۶ رقمی بازیابی
     */
    public function verifyPasswordResetCode(
        string $email,
        string $code
    ): bool {
        $email = strtolower(trim($email));

        $rateLimitKey = $this->passwordResetVerifyRateLimitKey(
            $email
        );

        if (
            RateLimiter::tooManyAttempts(
                $rateLimitKey,
                self::PASSWORD_RESET_VERIFY_MAX_ATTEMPTS
            )
        ) {
            return false;
        }

        if (!preg_match('/^\d{6}$/', $code)) {
            RateLimiter::hit(
                $rateLimitKey,
                self::PASSWORD_RESET_RATE_LIMIT_SECONDS
            );

            return false;
        }

        $reset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$reset || !$reset->created_at) {
            RateLimiter::hit(
                $rateLimitKey,
                self::PASSWORD_RESET_RATE_LIMIT_SECONDS
            );

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

            RateLimiter::hit(
                $rateLimitKey,
                self::PASSWORD_RESET_RATE_LIMIT_SECONDS
            );

            return false;
        }

        $isValid = Hash::check(
            $code,
            $reset->token
        );

        if (!$isValid) {
            RateLimiter::hit(
                $rateLimitKey,
                self::PASSWORD_RESET_RATE_LIMIT_SECONDS
            );
        }

        return $isValid;
    }

    /**
     * تغییر رمز عبور با کد بازیابی
     */
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
                'code' => [
                    'کد بازیابی نادرست یا منقضی شده است.'
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

        RateLimiter::clear(
            $this->passwordResetRequestRateLimitKey($email)
        );

        RateLimiter::clear(
            $this->passwordResetVerifyRateLimitKey($email)
        );
    }

    /**
     * کلید محدودیت درخواست کد
     */
    private function passwordResetRequestRateLimitKey(
        string $email
    ): string {
        return 'password-reset-request:' . hash(
            'sha256',
            $email
        );
    }

    /**
     * کلید محدودیت بررسی کد
     */
    private function passwordResetVerifyRateLimitKey(
        string $email
    ): string {
        return 'password-reset-verify:' . hash(
            'sha256',
            $email
        );
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
