<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('username', 50)->unique();

            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();

            $table->string('password');
            $table->string('pin')->nullable();

            $table->string('city')->nullable();
            $table->string('profile_photo')->nullable();

            $table->boolean('fingerprint_enabled')
                ->default(false);

            $table->timestamp('email_verified_at')
                ->nullable();

            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
