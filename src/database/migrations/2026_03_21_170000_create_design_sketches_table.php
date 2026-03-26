<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_sketches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->decimal('width_mm', 12, 2)->nullable();
            $table->decimal('height_mm', 12, 2)->nullable();
            $table->longText('canvas_json');
            $table->longText('preview_png')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('created_by_user_id');
            $table->index('quote_id');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_sketches');
    }
};
