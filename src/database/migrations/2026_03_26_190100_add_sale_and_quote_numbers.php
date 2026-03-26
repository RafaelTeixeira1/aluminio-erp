<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('sale_number', 40)->nullable()->unique()->after('id');
            $table->index('sale_number');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('quote_number', 40)->nullable()->unique()->after('id');
            $table->index('quote_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_sale_number_index');
            $table->dropUnique('sales_sale_number_unique');
            $table->dropColumn('sale_number');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('quotes_quote_number_index');
            $table->dropUnique('quotes_quote_number_unique');
            $table->dropColumn('quote_number');
        });
    }
};
