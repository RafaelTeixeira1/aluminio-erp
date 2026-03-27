@extends('layouts.app')

@section('title', 'Fornecedores')
@section('page-title', 'Gerenciar Fornecedores')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Fornecedores</h1>
        <p class="text-gray-600 mt-1">Gerencie seus fornecedores e histórico de compras</p>
    </div>
    <a href="{{ route('suppliers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        + Novo Fornecedor
    </a>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
        {{ session('success') }}
    </div>
@endif

<!-- Filtros de Busca -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('suppliers.index') }}" class="flex gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Buscar por nome ou documento..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos os status</option>
            <option value="active" @selected(($status ?? '') === 'active')>Ativos</option>
            <option value="inactive" @selected(($status ?? '') === 'inactive')>Inativos</option>
        </select>
        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
            Filtrar
        </button>
        <a href="{{ route('suppliers.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition">
            Limpar
        </a>
    </form>
</div>

<!-- Tabela de Fornecedores -->
<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contato</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Compras</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $supplier->name }}</p>
                        @if($supplier->contact_person)
                            <p class="text-xs text-gray-500">{{ $supplier->contact_person }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($supplier->phone)
                            <p class="text-sm text-gray-900">{{ $supplier->phone }}</p>
                        @endif
                        @if($supplier->email)
                            <p class="text-xs text-gray-500 truncate max-w-xs">{{ $supplier->email }}</p>
                        @else
                            <p class="text-xs text-gray-400">Sem e-mail</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $supplier->document ?: '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 text-center">{{ $supplier->purchase_orders_count }}</td>
                    <td class="px-6 py-4 text-sm text-center">
                        @if($supplier->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Ativo
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Inativo
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-right table-actions">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="text-green-600 hover:text-green-900">Ver</a>
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="text-blue-600 hover:text-blue-900">Editar</a>
                        @if($supplier->is_active)
                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="inline" 
                                  onsubmit="return confirm('Deseja desativar este fornecedor?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Desativar</button>
                            </form>
                        @else
                            <form action="{{ route('suppliers.restore', $supplier) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-green-600 hover:text-green-900">Reativar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                        Nenhum fornecedor encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($suppliers->hasPages())
    <div class="mt-4">
        {{ $suppliers->links() }}
    </div>
@endif
@endsection
