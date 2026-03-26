<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropUnique(['sale_id']);
            $table->unsignedInteger('installment_number')->default(1)->after('sale_id');
            $table->unsignedInteger('installment_count')->default(1)->after('installment_number');
            $table->index(['sale_id', 'installment_number']);
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropIndex(['sale_id', 'installment_number']);
            $table->dropColumn(['installment_number', 'installment_count']);
            $table->unique('sale_id');
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
        });
    }
};