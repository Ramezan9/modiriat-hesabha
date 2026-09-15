<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ایجاد جدول کدهای بازیابی رمز عبور
     */
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * حذف جدول کدهای بازیابی
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
