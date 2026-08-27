<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workspace_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('type', [
                'deposit',
                'withdrawal',
            ]);

            $table->enum('currency', [
                'AFN',
                'TOMAN',
                'USD',
                'TRY',
            ]);

            $table->decimal('amount', 20, 2);
            $table->string('amount_in_words')->nullable();

            $table->text('description')->nullable();

            $table->dateTime('transaction_date');

            $table->timestamps();

            $table->index([
                'workspace_id',
                'customer_id',
                'transaction_date',
            ]);

            $table->index([
                'currency',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
