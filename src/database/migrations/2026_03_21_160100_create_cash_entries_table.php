<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('origin_type', 60)->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->timestamp('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('occurred_at');
            $table->index(['origin_type', 'origin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_entries');
    }
};
