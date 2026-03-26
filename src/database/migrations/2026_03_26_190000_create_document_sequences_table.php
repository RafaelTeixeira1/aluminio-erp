<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique(); // PO_COMPRA, VD_VENDA, QT_ORCAMENTO, NFE_SAIDA
            $table->string('description', 160); // "Pedidos de Compra", "Vendas", etc
            $table->string('prefix', 20)->default(''); // "PC-", "V-", "Q-", "NF-"
            $table->integer('next_number')->default(1); // Próximo número a gerar
            $table->string('pattern', 60)->default('P-%06d'); // Padrão de formatação com placeholders
            $table->string('reset_frequency', 20)->default('never'); // never, annual, monthly
            $table->date('last_reset_at')->nullable();
            $table->integer('year_length')->default(4); // comprimento do ano (4 para 2026, ou 2 para 26)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });

        Schema::create('document_sequence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_sequence_id')->constrained('document_sequences')->cascadeOnDelete();
            $table->string('generated_number', 60); // "PC-000001", "V-2026-001", etc
            $table->string('document_type', 60)->nullable(); // "PurchaseOrder", "Sale", "Quote"
            $table->unsignedBigInteger('document_id')->nullable(); // ID do documento
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->useCurrent();

            $table->index(['document_type', 'document_id']);
            $table->index('document_sequence_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequence_logs');
        Schema::dropIfExists('document_sequences');
    }
};
