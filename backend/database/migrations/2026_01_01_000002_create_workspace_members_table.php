<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workspace_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('role', [
                'manager',
                'employee',
            ])->default('employee');

            $table->enum('status', [
                'active',
                'inactive',
                'pending',
            ])->default('active');

            $table->timestamps();

            $table->unique([
                'workspace_id',
                'user_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
    }
};
