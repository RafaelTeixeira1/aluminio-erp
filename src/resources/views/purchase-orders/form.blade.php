@extends('layouts.app')

@section('title', 'Novo Pedido de Compra')
@section('page-title', 'Novo Pedido de Compra')

@section('content')
<div class="max-w-4xl mx-auto">
    <a href="{{ route('purchase-orders.index') }}" class="text-blue-600 hover:text-blue-900 mb-6 inline-flex items-center">
        ← Voltar para Pedidos
    </a>

    <div class="bg-white rounded-lg shadow p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Criar Novo Pedido de Compra</h1>

        <form action="{{ route('purchase-orders.store') }}" method="POST" id="purchaseForm" class="space-y-6">
            @csrf

            <!-- Seleção de Fornecedor -->
            <div>
                <label for="supplier_id" class="block text-sm font-semibold text-gray-700 mb-2">
                    Fornecedor <span class="text-red-500">*</span>
                </label>
                <select id="supplier_id" name="supplier_id" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('supplier_id') border-red-500 @enderror"
                        required>
                    <option value="">Selecione um fornecedor...</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected($selectedSupplier?->id === $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Data de Entrega -->
            <div>
                <label for="delivery_date" class="block text-sm font-semibold text-gray-700 mb-2">
                    Data de Entrega Prevista
                </label>
                <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('delivery_date') border-red-500 @enderror">
                @error('delivery_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Itens do Pedido -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Itens do Pedido</h2>
                    <button type="button" id="addItemBtn" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                        + Adicionar Item
                    </button>
                </div>

                <div id="itemsContainer" class="space-y-4">
                    <!-- Items will be added here dynamically -->
                </div>

                @error('items')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
                @error('items.*.*')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notas -->
            <div>
                <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                    Notas
                </label>
                <textarea id="notes" name="notes" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror"
                          placeholder="Observações adicionais...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Resumo -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-semibold">Total do Pedido:</span>
                    <span class="text-2xl font-bold text-gray-900">R$ <span id="totalAmount">0,00</span></span>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                    Criar Pedido
                </button>
                <a href="{{ route('purchase-orders.index') }}" class="bg-gray-300 text-gray-900 px-6 py-2 rounded-lg hover:bg-gray-400 transition font-medium">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const catalogItems = @json($catalogItems);
let itemIndex = 0;

document.getElementById('addItemBtn').addEventListener('click', function() {
    addItemRow();
});

function addItemRow(data = null) {
    const container = document.getElementById('itemsContainer');
    const index = itemIndex++;
    
    const itemsOptions = catalogItems.map(item => 
        `<option value="${item.id}" ${data?.catalog_item_id == item.id ? 'selected' : ''}>${item.name}</option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'border rounded-lg p-4 bg-gray-50';
    row.id = `item-${index}`;
    row.innerHTML = `
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Produto *</label>
                <select name="items[${index}][catalog_item_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateItemTotal(${index})">
                    <option value="">Selecione...</option>
                    ${itemsOptions}
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Quantidade *</label>
                <input type="number" name="items[${index}][quantity]" value="${data?.quantity || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" step="0.01" min="0.01" required onchange="updateItemTotal(${index})">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Preço Unitário *</label>
                <input type="number" name="items[${index}][unit_cost]" value="${data?.unit_cost || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" step="0.01" min="0.01" required onchange="updateItemTotal(${index})">
            </div>
        </div>
        <div class="mt-3 flex justify-between items-center">
            <div class="text-sm">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-semibold text-gray-900" id="subtotal-${index}">R$ 0,00</span>
            </div>
            <button type="button" class="text-red-600 hover:text-red-900 text-sm font-medium" onclick="removeItemRow(${index})">
                Remover
            </button>
        </div>
    `;
    
    container.appendChild(row);
}

function removeItemRow(index) {
    document.getElementById(`item-${index}`).remove();
    updateTotal();
}

function updateItemTotal(index) {
    const quantity = parseFloat(document.querySelector(`input[name="items[${index}][quantity]"]`)?.value || 0);
    const unitCost = parseFloat(document.querySelector(`input[name="items[${index}][unit_cost]"]`)?.value || 0);
    const subtotal = quantity * unitCost;
    
    document.getElementById(`subtotal-${index}`).textContent = 
        'R$ ' + subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    updateTotal();
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('input[name*="[unit_cost]"]').forEach((input, idx) => {
        const row = input.closest('.border');
        const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]')?.value || 0);
        const unitCost = parseFloat(input.value || 0);
        total += quantity * unitCost;
    });
    
    document.getElementById('totalAmount').textContent = 
        total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Add one empty item row on load
addItemRow();
</script>
@endsection
