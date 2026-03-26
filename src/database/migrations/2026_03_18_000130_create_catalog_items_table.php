<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type');
            $table->decimal('price', 12, 2);
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('stock_minimum', 12, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('item_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
