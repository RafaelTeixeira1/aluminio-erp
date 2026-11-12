<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unidade de venda do produto: unidade, pacote, barra (perfil de 6 m) ou m2 (vidro).
     * Define qual criador do Factory Method monta o item do orcamento.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('sale_unit', 20)->default('unidade')->after('item_type');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('sale_unit');
        });
    }
};
