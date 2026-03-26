<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('settled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('aberto');
            $table->decimal('amount_total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->date('due_date')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};