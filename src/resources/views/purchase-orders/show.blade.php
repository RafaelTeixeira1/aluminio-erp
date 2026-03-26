@extends('layouts.app')

@section('title', 'Pedido: ' . $purchaseOrder->order_number)
@section('page-title', $purchaseOrder->order_number)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('purchase-orders.index') }}" class="text-blue-600 hover:text-blue-900 inline-flex items-center">
        ← Voltar para Pedidos
    </a>
    @if($purchaseOrder->status !== 'cancelado' && $purchaseOrder->status !== 'recebido')
        <form action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" method="POST" class="inline"
              onsubmit="return confirm('Cancelar este pedido? Esta ação não pode ser desfeita.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                Cancelar Pedido
            </button>
        </form>
    @endif
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informações Principais -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Cabeçalho do Pedido -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase mb-1">Número do Pedido</h3>
                    <p class="text-2xl font-bold text-gray-900">{{ $purchaseOrder->order_number }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase mb-1">Status</h3>
                    <div>
                        @if($purchaseOrder->status === 'aberto')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                Aberto
                            </span>
                        @elseif($purchaseOrder->status === 'parcial')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                Parcialmente Recebido
                            </span>
                        @elseif($purchaseOrder->status === 'recebido')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                Totalmente Recebido
                            </span>
                        @elseif($purchaseOrder->status === 'cancelado')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                Cancelado
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6 pt-6 border-t border-gray-200">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase mb-1">Fornecedor</h3>
                    <a href="{{ route('suppliers.show', $purchaseOrder->supplier) }}" class="text-blue-600 hover:text-blue-900 font-semibold">
                        {{ $purchaseOrder->supplier->name }}
                    </a>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase mb-1">Data do Pedido</h3>
                    <p class="text-gray-900">{{ $purchaseOrder->created_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($purchaseOrder->delivery_date)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-1">Data de Entrega Prevista</h3>
                        <p class="text-gray-900">{{ $purchaseOrder->delivery_date->format('d/m/Y') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Itens do Pedido -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Itens do Pedido</h2>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty Pedida</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty Recebida</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Falta Receber</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço Unit.</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($purchaseOrder->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-900">
                                    <a href="{{ route('catalog-items.show', $item->catalogItem) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ $item->catalogItem->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 text-sm text-center text-gray-900">
                                    {{ number_format($item->quantity, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-sm text-center text-gray-900 font-semibold">
                                    {{ number_format($item->quantity_received, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-sm text-center">
                                    @php
                                        $pending = $item->quantity - $item->quantity_received;
                                    @endphp
                                    @if($pending > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {{ number_format($pending, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-green-600 text-sm">✓</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-right text-gray-900">
                                    R$ {{ number_format($item->unit_cost, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-sm text-right text-gray-900 font-semibold">
                                    R$ {{ number_format($item->line_total, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-sm text-center">
                                    @if($purchaseOrder->status !== 'cancelado' && $pending > 0)
                                        <button type="button" class="text-green-600 hover:text-green-900 font-medium" 
                                                onclick="openReceiveModal({{ $item->id }}, '{{ $item->catalogItem->name }}', {{ $pending }})">
                                            Receber
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <th colspan="4" class="px-4 py-4 text-left text-sm font-semibold text-gray-900">TOTAL</th>
                            <td colspan="2" class="px-4 py-4 text-right">
                                <span class="text-lg font-bold text-gray-900">
                                    R$ {{ number_format($purchaseOrder->total, 2, ',', '.') }}
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Histórico de Recebimentos -->
        @if($purchaseOrder->items->flatMap->receipts->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Histórico de Recebimentos</h2>

                <div class="space-y-4">
                    @foreach($purchaseOrder->items as $item)
                        @foreach($item->receipts->sortByDesc('created_at') as $receipt)
                            <div class="border-l-4 border-green-500 bg-green-50 p-4 rounded">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $item->catalogItem->name }}</p>
                                        <p class="text-sm text-gray-600">Quantidade: {{ number_format($receipt->quantity_received, 2, ',', '.') }}</p>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        {{ $receipt->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">Recebido por: {{ $receipt->user->name }}</p>
                                @if($receipt->notes)
                                    <p class="text-sm text-gray-600 mt-2">{{ $receipt->notes }}</p>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Painel Lateral -->
    <div class="lg:col-span-1 space-y-4">
        <!-- Resumo -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Resumo</h3>
            
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Itens:</dt>
                    <dd class="font-semibold text-gray-900">{{ $purchaseOrder->items->count() }}</dd>
                </div>
                <div class="flex justify-between text-lg">
                    <dt class="font-semibold text-gray-900">Total:</dt>
                    <dd class="font-bold text-gray-900">R$ {{ number_format($purchaseOrder->total, 2, ',', '.') }}</dd>
                </div>
                <div class="border-t pt-3 flex justify-between">
                    <dt class="text-gray-600">Recebido:</dt>
                    <dd class="font-semibold text-green-600">
                        R$ {{ number_format($purchaseOrder->items->sum(fn($i) => $i->unit_cost * $i->quantity_received), 2, ',', '.') }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Pendente:</dt>
                    <dd class="font-semibold text-yellow-600">
                        R$ {{ number_format($purchaseOrder->total - $purchaseOrder->items->sum(fn($i) => $i->unit_cost * $i->quantity_received), 2, ',', '.') }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Progresso -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Progresso do Recebimento</h3>
            
            @php
                $totalItems = $purchaseOrder->items->sum('quantity');
                $receivedItems = $purchaseOrder->items->sum('quantity_received');
                $progress = $totalItems > 0 ? ($receivedItems / $totalItems) * 100 : 0;
            @endphp

            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                <div class="bg-blue-600 h-3 rounded-full transition-all" style="width: {{ $progress }}%"></div>
            </div>
            <p class="text-sm text-gray-600 text-center">
                {{ number_format($receivedItems, 2, ',', '.') }} / {{ number_format($totalItems, 2, ',', '.') }} unidades ({{ round($progress) }}%)
            </p>
        </div>

        @if($purchaseOrder->payable)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Conta a Pagar</h3>
                
                <a href="{{ route('payables.show', $purchaseOrder->payable) }}" class="text-blue-600 hover:text-blue-900 font-semibold block mb-3">
                    {{ $purchaseOrder->payable->reference }}
                </a>
                
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Valor:</dt>
                        <dd class="font-semibold">R$ {{ number_format($purchaseOrder->payable->amount, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Status:</dt>
                        <dd class="font-semibold">
                            @if($purchaseOrder->payable->paid_at)
                                <span class="text-green-600">Pago</span>
                            @else
                                <span class="text-red-600">Pendente</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        @endif
    </div>
</div>

<!-- Modal para Receber Item -->
<div id="receiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Receber Item</h2>

        <form method="POST" id="receiveForm">
            @csrf
            <input type="hidden" id="itemId" name="purchase_order_item_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Produto</label>
                    <p id="productName" class="text-gray-900 font-medium"></p>
                </div>

                <div>
                    <label for="maxQuantity" class="block text-sm font-semibold text-gray-700 mb-1">Máximo a Receber</label>
                    <p id="maxQuantity" class="text-gray-900 font-medium"></p>
                </div>

                <div>
                    <label for="quantity_received" class="block text-sm font-semibold text-gray-700 mb-1">
                        Quantidade a Receber <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="quantity_received" name="quantity_received" step="0.01" min="0.01" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div>
                    <label for="received_at" class="block text-sm font-semibold text-gray-700 mb-1">Data do Recebimento</label>
                    <input type="date" id="received_at" name="received_at" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">Notas</label>
                    <textarea id="notes" name="notes" rows="2" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Observações sobre o recebimento..."></textarea>
                </div>
            </div>

            <div class="flex gap-4 pt-6 mt-4 border-t border-gray-200">
                <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition font-medium">
                    Confirmar Recebimento
                </button>
                <button type="button" class="flex-1 bg-gray-300 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-400 transition font-medium" 
                        onclick="closeReceiveModal()">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openReceiveModal(itemId, productName, maxQuantity) {
    document.getElementById('itemId').value = itemId;
    document.getElementById('productName').textContent = productName;
    document.getElementById('maxQuantity').textContent = maxQuantity.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('quantity_received').max = maxQuantity;
    document.getElementById('quantity_received').value = '';
    document.getElementById('received_at').value = new Date().toISOString().split('T')[0];
    document.getElementById('notes').value = '';
    document.getElementById('receiveModal').classList.remove('hidden');
}

function closeReceiveModal() {
    document.getElementById('receiveModal').classList.add('hidden');
}

document.getElementById('receiveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const itemId = document.getElementById('itemId').value;
    const formData = new FormData(this);
    
    fetch(`{{ route('purchase-orders.index') }}/../${{{ $purchaseOrder->id }}}/itens/${itemId}/receber`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
        },
        body: formData
    }).then(response => {
        if (response.ok) {
            window.location.reload();
        } else {
            alert('Erro ao receber item');
        }
    });
});
</script>
@endsection
