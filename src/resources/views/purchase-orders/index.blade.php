@extends('layouts.app')

@section('title', 'Pedidos de Compra')
@section('page-title', 'Gerenciar Pedidos de Compra')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pedidos de Compra</h1>
        <p class="text-gray-600 mt-1">Gerencie seus pedidos de compra e recebimentos</p>
    </div>
    <a href="{{ route('purchase-orders.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        + Novo Pedido
    </a>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">
        {{ session('error') }}
    </div>
@endif

<!-- Filtros de Busca -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('purchase-orders.index') }}" class="flex gap-4 flex-wrap">
        <div class="flex-1 min-w-xs">
            <input type="text" name="search" value="{{ $search ?? '' }}" 
                   placeholder="Buscar por número de pedido..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <select name="supplier_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos os fornecedores</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(($supplier_id ?? '') == $supplier->id)>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>

        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos os status</option>
            <option value="aberto" @selected(($status ?? '') === 'aberto')>Aberto</option>
            <option value="parcial" @selected(($status ?? '') === 'parcial')>Parcialmente Recebido</option>
            <option value="recebido" @selected(($status ?? '') === 'recebido')>Recebido</option>
            <option value="cancelado" @selected(($status ?? '') === 'cancelado')>Cancelado</option>
        </select>

        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
            Filtrar
        </button>
        <a href="{{ route('purchase-orders.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition">
            Limpar
        </a>
    </form>
</div>

<!-- Tabela de Pedidos -->
<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Itens</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($purchaseOrders as $purchase)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('purchase-orders.show', $purchase) }}" class="text-blue-600 hover:text-blue-900 font-semibold">
                            {{ $purchase->order_number }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="text-gray-900 hover:text-blue-600">
                            {{ $purchase->supplier->name }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $purchase->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-center text-gray-900">
                        {{ $purchase->items->count() }}
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">
                        R$ {{ number_format($purchase->total, 2, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-center">
                        @if($purchase->status === 'aberto')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Aberto
                            </span>
                        @elseif($purchase->status === 'parcial')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Parcial
                            </span>
                        @elseif($purchase->status === 'recebido')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Recebido
                            </span>
                        @elseif($purchase->status === 'cancelado')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Cancelado
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-right space-y-1 sm:space-y-0 sm:space-x-2">
                        <a href="{{ route('purchase-orders.show', $purchase) }}" class="text-green-600 hover:text-green-900">Ver</a>
                        @if($purchase->status !== 'cancelado' && $purchase->status !== 'recebido')
                            <form action="{{ route('purchase-orders.cancel', $purchase) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Cancelar este pedido?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-red-600 hover:text-red-900">Cancelar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                        Nenhum pedido de compra encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($purchaseOrders->hasPages())
    <div class="mt-4">
        {{ $purchaseOrders->links() }}
    </div>
@endif
@endsection
