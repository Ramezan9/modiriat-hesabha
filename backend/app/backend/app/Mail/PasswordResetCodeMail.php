<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code
    ) {
    }

    public function build(): static
    {
        return $this
            ->subject('کد بازیابی رمز عبور - مدیریت حساب‌ها')
            ->html('
                <div dir="rtl" style="font-family:Arial,sans-serif;line-height:1.8">
                    <h2>مدیریت حساب‌ها</h2>
                    <p>کد بازیابی رمز عبور شما:</p>

                    <div style="
                        font-size:32px;
                        font-weight:bold;
                        letter-spacing:8px;
                        padding:15px;
                        text-align:center;
                        background:#f3f3f3;
                        border-radius:10px;
                    ">
                        ' . e($this->code) . '
                    </div>

                    <p>این کد تا ۱۰ دقیقه اعتبار دارد.</p>
                    <p>اگر شما درخواست بازیابی رمز عبور نداده‌اید، این ایمیل را نادیده بگیرید.</p>
                </div>
            ');
    }
}
