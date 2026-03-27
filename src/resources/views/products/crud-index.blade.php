@extends('layouts.app')

@section('title', 'Produtos')
@section('page-title', 'Gerenciar Produtos')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
        + Novo Produto
    </a>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Imagem</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marca/Linha</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kg/m</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Estoque</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-700">
                        @php
                            $tableImage = $product->primaryImage?->image_path ?? $product->image_path;
                        @endphp
                        @if(!empty($tableImage))
                            <img src="{{ asset($tableImage) }}" alt="Imagem do produto {{ $product->name }}" class="w-12 h-12 object-cover rounded border border-gray-200">
                        @else
                            <span class="text-xs text-gray-400">Sem imagem</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $product->category->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $product->item_type }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $product->material ?: '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ trim((string) (($product->brand ?: '').' / '.($product->product_line ?: '')), ' /') ?: '-' }}</td>
                    <td class="px-6 py-4 text-sm text-right text-gray-700">{{ $product->weight_per_meter_kg !== null ? number_format((float) $product->weight_per_meter_kg, 3, ',', '.') : '-' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-right">R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                    <td class="px-6 py-4 text-sm text-right">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $product->stock < $product->stock_minimum ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                            {{ $product->stock }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $product->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-right table-actions">
                        <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:text-blue-900">Editar</a>
                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Deletar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="px-6 py-8 text-center text-gray-500">
                        Nenhum produto cadastrado
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($products->hasPages())
    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endif
@endsection
