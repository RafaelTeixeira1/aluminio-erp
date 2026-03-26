<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('image_kind', 30)->default('outro');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['catalog_item_id', 'is_primary']);
            $table->index(['catalog_item_id', 'image_kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_images');
    }
};
