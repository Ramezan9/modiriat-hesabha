<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workspace_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('profile_photo')->nullable();

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'workspace_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
