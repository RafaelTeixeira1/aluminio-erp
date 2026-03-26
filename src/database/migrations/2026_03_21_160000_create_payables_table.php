<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_name', 160);
            $table->string('description', 200);
            $table->string('category', 80)->default('geral');
            $table->string('document_number', 80)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('settled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('aberto');
            $table->decimal('amount_total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('vendor_name');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
