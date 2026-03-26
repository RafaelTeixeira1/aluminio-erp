<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('material', 120)->nullable()->after('weight_per_meter_kg');
            $table->string('finish', 120)->nullable()->after('material');
            $table->decimal('thickness_mm', 10, 3)->nullable()->after('finish');
            $table->decimal('standard_width_mm', 10, 2)->nullable()->after('thickness_mm');
            $table->decimal('standard_height_mm', 10, 2)->nullable()->after('standard_width_mm');
            $table->string('brand', 120)->nullable()->after('standard_height_mm');
            $table->string('product_line', 120)->nullable()->after('brand');
            $table->text('technical_notes')->nullable()->after('product_line');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn([
                'material',
                'finish',
                'thickness_mm',
                'standard_width_mm',
                'standard_height_mm',
                'brand',
                'product_line',
                'technical_notes',
            ]);
        });
    }
};
