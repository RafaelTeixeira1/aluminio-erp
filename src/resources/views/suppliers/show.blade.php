@extends('layouts.app')

@section('title', 'Fornecedor: ' . $supplier->name)
@section('page-title', $supplier->name)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('suppliers.index') }}" class="text-blue-600 hover:text-blue-900 inline-flex items-center">
        ← Voltar para Fornecedores
    </a>
    <div class="space-x-3">
        <a href="{{ route('suppliers.edit', $supplier) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            Editar
        </a>
        @if($supplier->is_active)
            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="inline" 
                  onsubmit="return confirm('Deseja realmente desativar este fornecedor?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    Desativar
                </button>
            </form>
        @else
            <form action="{{ route('suppliers.restore', $supplier) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    Reativar
                </button>
            </form>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Informações do Fornecedor -->
    <div class="md:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Informações Gerais</h2>
            
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-semibold text-gray-700">Nome</dt>
                    <dd class="text-lg text-gray-900">{{ $supplier->name }}</dd>
                </div>

                @if($supplier->document)
                    <div>
                        <dt class="text-sm font-semibold text-gray-700">CNPJ/CPF</dt>
                        <dd class="text-gray-900">{{ $supplier->document }}</dd>
                    </div>
                @endif

                @if($supplier->contact_person)
                    <div>
                        <dt class="text-sm font-semibold text-gray-700">Pessoa de Contato</dt>
                        <dd class="text-gray-900">{{ $supplier->contact_person }}</dd>
                    </div>
                @endif

                @if($supplier->phone)
                    <div>
                        <dt class="text-sm font-semibold text-gray-700">Telefone</dt>
                        <dd class="text-gray-900">{{ $supplier->phone }}</dd>
                    </div>
                @endif

                @if($supplier->email)
                    <div>
                        <dt class="text-sm font-semibold text-gray-700">E-mail</dt>
                        <dd class="text-gray-900 break-all">{{ $supplier->email }}</dd>
                    </div>
                @endif

                @if($supplier->address)
                    <div>
                        <dt class="text-sm font-semibold text-gray-700">Endereço</dt>
                        <dd class="text-gray-900">{{ $supplier->address }}</dd>
                    </div>
                @endif

                @if($supplier->city)
                    <div>
                        <dt class="text-sm font-semibold text-gray-700">Cidade</dt>
                        <dd class="text-gray-900">{{ $supplier->city }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-sm font-semibold text-gray-700">Status</dt>
                    <dd>
                        @if($supplier->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                Ativo
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                Inativo
                            </span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-semibold text-gray-700">Data de Criação</dt>
                    <dd class="text-gray-900">{{ $supplier->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>

            @if($supplier->notes)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Notas</h3>
                    <p class="text-gray-900 whitespace-pre-wrap">{{ $supplier->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Histórico de Compras -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Histórico de Compras</h2>
                <a href="{{ route('purchase-orders.create') }}?supplier_id={{ $supplier->id }}" 
                   class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                    + Nova Compra
                </a>
            </div>

            @if($supplier->purchaseOrders->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Itens</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($supplier->purchaseOrders as $purchase)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $purchase->order_number }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $purchase->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-900">
                                        {{ $purchase->items->count() }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 font-semibold">
                                        R$ {{ number_format($purchase->total, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
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
                                    <td class="px-4 py-3 text-sm text-center">
                                        <a href="{{ route('purchase-orders.show', $purchase) }}" class="text-blue-600 hover:text-blue-900">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-6">Nenhuma compra registrada para este fornecedor.</p>
            @endif
        </div>
    </div>

    <!-- Painel Lateral de Estatísticas -->
    <div class="md:col-span-1 space-y-4">
        <!-- Total de Compras -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-2">Total de Compras</h3>
            <p class="text-3xl font-bold text-gray-900">
                {{ $supplier->purchaseOrders->count() }}
            </p>
            <p class="text-xs text-gray-500 mt-1">pedidos registrados</p>
        </div>

        <!-- Total Investido -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-2">Total Investido</h3>
            <p class="text-2xl font-bold text-gray-900">
                R$ {{ number_format($supplier->purchaseOrders->sum('total'), 2, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 mt-1">em todas as compras</p>
        </div>

        <!-- Itens Diferentes -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-2">Produtos</h3>
            <p class="text-3xl font-bold text-gray-900">
                {{ $supplier->purchaseOrders->flatMap->items->unique('catalog_item_id')->count() }}
            </p>
            <p class="text-xs text-gray-500 mt-1">produtos diferentes</p>
        </div>
    </div>
</div>
@endsection
