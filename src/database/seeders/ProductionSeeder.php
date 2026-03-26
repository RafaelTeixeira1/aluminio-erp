<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Usuários
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@sdaluminios.com.br',
            'password' => Hash::make('12345678'),
            'profile' => 'admin',
            'active' => true,
        ]);

        User::create([
            'name' => 'Gerente de Vendas',
            'email' => 'vendedor@sdaluminios.com.br',
            'password' => Hash::make('12345678'),
            'profile' => 'vendedor',
            'active' => true,
        ]);

        User::create([
            'name' => 'Gerente de Estoque',
            'email' => 'estoque@sdaluminios.com.br',
            'password' => Hash::make('12345678'),
            'profile' => 'estoque',
            'active' => true,
        ]);

        // Categorias
        $perfis = Category::create(['name' => 'Perfis', 'active' => true]);
        $chapinhas = Category::create(['name' => 'Chapinhas', 'active' => true]);
        $acessorios = Category::create(['name' => 'Acessórios', 'active' => true]);

        // Produtos - Perfis
        CatalogItem::create([
            'category_id' => $perfis->id,
            'name' => 'Perfil Alumínio 40x20',
            'item_type' => 'produto',
            'price' => 45.50,
            'stock' => 250,
            'stock_minimum' => 50,
            'is_active' => true,
        ]);

        CatalogItem::create([
            'category_id' => $perfis->id,
            'name' => 'Perfil Alumínio 30x30',
            'item_type' => 'produto',
            'price' => 35.00,
            'stock' => 5,
            'stock_minimum' => 50,
            'is_active' => true,
        ]);

        CatalogItem::create([
            'category_id' => $perfis->id,
            'name' => 'Perfil Alumínio 50x50',
            'item_type' => 'produto',
            'price' => 65.00,
            'stock' => 120,
            'stock_minimum' => 30,
            'is_active' => true,
        ]);

        // Produtos - Chapinhas
        CatalogItem::create([
            'category_id' => $chapinhas->id,
            'name' => 'Chapinha 60x60',
            'item_type' => 'produto',
            'price' => 32.00,
            'stock' => 45,
            'stock_minimum' => 50,
            'is_active' => true,
        ]);

        CatalogItem::create([
            'category_id' => $chapinhas->id,
            'name' => 'Chapinha 40x40',
            'item_type' => 'produto',
            'price' => 24.50,
            'stock' => 156,
            'stock_minimum' => 50,
            'is_active' => true,
        ]);

        // Produtos - Acessórios
        $acessorio1 = CatalogItem::create([
            'category_id' => $acessorios->id,
            'name' => 'Acessórios em Geral',
            'item_type' => 'acessorio',
            'price' => 18.90,
            'stock' => 380,
            'stock_minimum' => 100,
            'is_active' => true,
        ]);

        $acessorio2 = CatalogItem::create([
            'category_id' => $acessorios->id,
            'name' => 'Parafusos e Porcas',
            'item_type' => 'acessorio',
            'price' => 12.00,
            'stock' => 500,
            'stock_minimum' => 200,
            'is_active' => true,
        ]);

        // Clientes
        $client1 = Client::create([
            'name' => 'Construtora ABC',
            'email' => 'contato@construtorabc.com.br',
            'phone' => '(11) 3456-7890',
            'address' => 'Rua das Flores, 123, São Paulo - SP',
            'document' => '12.345.678/0001-90',
        ]);

        $client2 = Client::create([
            'name' => 'Metal Steel Ltda',
            'email' => 'vendas@metalsteel.com.br',
            'phone' => '(11) 2345-6789',
            'address' => 'Av. Industrial, 456, Campinas - SP',
            'document' => '98.765.432/0001-10',
        ]);

        // Orçamentos
        $user = User::where('profile', 'admin')->first();
        
        $quote1 = Quote::create([
            'client_id' => $client1->id,
            'created_by_user_id' => $user->id,
            'status' => 'aberto',
            'subtotal' => 12500.00,
            'discount' => 0,
            'total' => 12500.00,
            'valid_until' => Carbon::now()->addDays(7),
        ]);

        QuoteItem::create([
            'quote_id' => $quote1->id,
            'catalog_item_id' => $perfis->catalogItems()->first()->id,
            'item_name' => 'Perfil de Alumínio 20x30',
            'item_type' => 'produto',
            'quantity' => 100,
            'unit_price' => 45.50,
            'line_total' => 4550.00,
        ]);

        // Vendas
        $sale1 = Sale::create([
            'client_id' => $client1->id,
            'quote_id' => $quote1->id,
            'created_by_user_id' => $user->id,
            'status' => 'confirmada',
            'subtotal' => 8500.00,
            'discount' => 0,
            'total' => 8500.00,
            'confirmed_at' => Carbon::now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale1->id,
            'catalog_item_id' => $perfis->catalogItems()->first()->id,
            'quantity' => 50,
            'unit_price' => 45.50,
            'item_name' => 'Perfil Alumínio 40x20',
            'item_type' => 'produto',
            'line_total' => 2275.00,
        ]);

        $sale2 = Sale::create([
            'client_id' => $client2->id,
            'created_by_user_id' => $user->id,
            'status' => 'confirmada',
            'subtotal' => 3200.00,
            'discount' => 0,
            'total' => 3200.00,
            'confirmed_at' => Carbon::now()->subDay(),
        ]);

        SaleItem::create([
            'sale_id' => $sale2->id,
            'catalog_item_id' => $chapinhas->catalogItems()->first()->id,
            'quantity' => 100,
            'unit_price' => 32.00,
            'item_name' => 'Chapinha 60x60',
            'item_type' => 'produto',
            'line_total' => 3200.00,
        ]);
    }
}
