@extends('layouts.app')

@section('title', 'Produtos')
@section('page-title', 'Catálogo de Produtos')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Produtos</h1>
                <p class="text-gray-600 mt-1">Visualização rápida dos produtos cadastrados</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Novo Produto</a>
                <a href="{{ route('products.crud') }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Gerenciar Tabela</a>
            </div>
        </div>

        <form method="GET" action="{{ route('products.index') }}" class="bg-white rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome do produto" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Categoria</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (int) ($filters['category_id'] ?? 0) === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                    <select name="item_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Todos</option>
                        <option value="produto" {{ ($filters['item_type'] ?? '') === 'produto' ? 'selected' : '' }}>Produto</option>
                        <option value="acessorio" {{ ($filters['item_type'] ?? '') === 'acessorio' ? 'selected' : '' }}>Acessório</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Todos</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Ativo</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Filtrar</button>
                    <a href="{{ route('products.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Limpar</a>
                </div>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($products as $product)
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
                    <div class="h-44 bg-gray-100 flex items-center justify-center overflow-hidden">
                        @php
                            $cardImage = $product->primaryImage?->image_path ?? $product->image_path;
                        @endphp
                        @if(!empty($cardImage))
                            <img src="{{ asset($cardImage) }}" alt="Imagem do produto {{ $product->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-sm text-gray-400">Sem imagem</span>
                        @endif
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="text-base font-semibold text-gray-900 leading-tight">{{ $product->name }}</h3>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                {{ $product->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>

                        <div class="space-y-1.5 mb-4 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Categoria:</span><span class="text-gray-800">{{ $product->category->name ?? 'N/A' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Tipo:</span><span class="text-gray-800">{{ ucfirst($product->item_type) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Material:</span><span class="text-gray-800">{{ $product->material ?: '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Marca/Linha:</span><span class="text-gray-800">{{ trim((string) (($product->brand ?: '').' / '.($product->product_line ?: '')), ' /') ?: '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Kg/m:</span><span class="text-gray-800">{{ $product->weight_per_meter_kg !== null ? number_format((float) $product->weight_per_meter_kg, 3, ',', '.') : '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Preço:</span><span class="font-semibold text-gray-900">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Estoque:</span><span class="{{ (float) $product->stock <= (float) $product->stock_minimum ? 'text-red-600 font-semibold' : 'text-green-700 font-semibold' }}">{{ number_format((float) $product->stock, 3, ',', '.') }}</span></div>
                        </div>

                        <a href="{{ route('products.edit', $product) }}" class="inline-flex w-full justify-center bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 transition text-sm font-medium">Editar Produto</a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-lg shadow p-10 text-center text-gray-500">
                    Nenhum produto cadastrado.
                </div>
            @endforelse
        </div>

        @if($products->hasPages())
            <div class="bg-white rounded-lg shadow px-6 py-4">
                {{ $products->links() }}
            </div>
        @endif
        </div>
    </div>
@endsection
