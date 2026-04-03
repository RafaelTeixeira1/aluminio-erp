@extends('layouts.app')

@section('title', $sale ? 'Editar Itens da Venda' : 'Nova Venda')
@section('page-title', $sale ? 'Editar Itens da Venda' : 'Nova Venda')

@section('content')
@php
    $productsData = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'item_type' => $product->item_type,
        'price' => (float) $product->price,
        'stock' => (float) $product->stock,
        'stock_minimum' => (float) $product->stock_minimum,
    ])->values();

    $initialItems = old('items');
    if (!is_array($initialItems) || $initialItems === []) {
        $initialItems = $sale
            ? $sale->items->map(fn ($item) => [
                'catalog_item_id' => $item->catalog_item_id,
                'item_name' => $item->item_name,
                'item_type' => $item->item_type,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->values()->all()
            : [
                ['catalog_item_id' => '', 'item_name' => '', 'item_type' => 'produto', 'quantity' => '', 'unit_price' => ''],
            ];
    }

    if ($initialItems === []) {
        $initialItems[] = ['catalog_item_id' => '', 'item_name' => '', 'item_type' => 'produto', 'quantity' => '', 'unit_price' => ''];
    }
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $sale ? 'Editar Itens da Venda #' . $sale->id : 'Nova Venda' }}</h1>
                <p class="text-sm text-gray-600 mt-1">Fluxo guiado para montar a venda com cálculo automático e validação antes de salvar.</p>
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800">1. Dados</span>
                <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800">2. Itens</span>
                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800">3. Revisão</span>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-800">Existem campos inválidos. Revise os itens antes de salvar.</p>
            <p class="text-xs text-red-700 mt-1">{{ $errors->first() }}</p>
        </div>
    @endif

    @if($sale)
        <form id="saleForm" action="{{ route('sales.updateItems', $sale) }}" method="POST" class="space-y-6">
        @method('PUT')
    @else
        <form id="saleForm" action="{{ route('sales.store') }}" method="POST" class="space-y-6">
    @endif
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <section class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">1. Dados da venda</h2>
                    <p class="text-sm text-gray-600">Defina cliente e condição comercial.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                        <select id="clientField" name="client_id" {{ $sale ? 'disabled' : '' }} class="w-full px-3 py-2 border border-gray-300 rounded-lg {{ $sale ? 'bg-gray-100 text-gray-500' : '' }}">
                            <option value="">Selecione...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $sale->client_id ?? '') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">{{ $sale ? 'Cliente fixo para venda já criada.' : 'Opcional para venda rápida.' }}</p>
                        @if($sale)
                            <input type="hidden" name="client_id" value="{{ $sale->client_id }}">
                        @endif
                    </div>

                    @unless($sale)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Desconto (R$)</label>
                            <input id="discountField" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Aplicado sobre o subtotal dos itens.</p>
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <input type="text" value="{{ ucfirst((string) $sale->status) }}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                        </div>
                    @endunless
                </div>
            </section>

            <aside class="bg-slate-900 text-white rounded-xl shadow-sm p-5 space-y-3">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-300">Resumo em tempo real</h3>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-300">Itens válidos</span>
                    <strong id="summaryItems">0</strong>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-300">Alertas de estoque</span>
                    <strong id="summaryStockAlerts">0</strong>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-300">Subtotal</span>
                    <strong id="summarySubtotal">R$ 0,00</strong>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-300">Desconto</span>
                    <strong id="summaryDiscount">R$ 0,00</strong>
                </div>
                <div class="pt-3 mt-2 border-t border-slate-700 flex items-center justify-between">
                    <span class="text-sm font-semibold">Total estimado</span>
                    <strong id="summaryTotal" class="text-lg">R$ 0,00</strong>
                </div>
                <p id="summaryHint" class="text-xs text-amber-300 hidden">Adicione ao menos um item válido para continuar.</p>
            </aside>
        </div>

        <section class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">2. Itens da venda</h2>
                    <p class="text-sm text-gray-600">Selecione produto, ajuste quantidades e confira os totais por linha.</p>
                </div>
                <button type="button" id="addItemBtn" class="self-start md:self-auto bg-blue-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-700">
                    + Adicionar item
                </button>
            </div>

            <div id="itemsContainer" class="space-y-3"></div>
        </section>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <button id="submitBtn" type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                    {{ $sale ? 'Atualizar Itens' : 'Salvar Venda' }}
                </button>
                <a href="{{ route('sales.index') }}" class="bg-gray-200 text-gray-800 px-5 py-2.5 rounded-lg hover:bg-gray-300 text-center">Cancelar</a>
            </div>
        </div>
    </form>
</div>

<template id="itemRowTemplate">
    <div class="item-row border border-gray-200 rounded-lg p-3">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Produto</label>
                <select data-field="catalog_item_id" class="w-full px-2 py-2 border border-gray-300 rounded text-sm"></select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                <select data-field="item_type" class="w-full px-2 py-2 border border-gray-300 rounded text-sm">
                    <option value="produto">produto</option>
                    <option value="acessorio">acessorio</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Descrição</label>
                <input data-field="item_name" type="text" class="w-full px-2 py-2 border border-gray-300 rounded text-sm" placeholder="Nome do item">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Qtd</label>
                <input data-field="quantity" type="number" min="0" step="0.001" class="w-full px-2 py-2 border border-gray-300 rounded text-sm" placeholder="0">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Preço unit. (R$)</label>
                <input data-field="unit_price" type="number" min="0" step="0.01" class="w-full px-2 py-2 border border-gray-300 rounded text-sm" placeholder="0,00">
            </div>
            <div class="md:col-span-1 flex items-end">
                <div class="w-full space-y-1">
                    <button type="button" data-action="duplicate" class="w-full px-2 py-2 border border-blue-200 text-blue-700 rounded text-sm hover:bg-blue-50">Duplicar</button>
                    <button type="button" data-action="remove" class="w-full px-2 py-2 border border-red-200 text-red-600 rounded text-sm hover:bg-red-50">Remover</button>
                </div>
            </div>
        </div>
        <div class="mt-2 flex flex-col md:flex-row md:items-center md:justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <span data-field="status" class="font-semibold text-amber-700 bg-amber-100 px-2 py-1 rounded-full">Incompleto</span>
                <span data-field="stock_warning" class="hidden font-semibold text-orange-800 bg-orange-100 px-2 py-1 rounded-full"></span>
            </div>
            <span class="text-gray-600">Total da linha: <strong data-field="line_total">R$ 0,00</strong></span>
        </div>
    </div>
</template>

<script type="application/json" id="products-data">{!! json_encode($productsData) !!}</script>
<script type="application/json" id="initial-items-data">{!! json_encode($initialItems) !!}</script>

<script>
const products = JSON.parse(document.getElementById('products-data').textContent || '[]');
const initialItems = JSON.parse(document.getElementById('initial-items-data').textContent || '[]');

const itemsContainer = document.getElementById('itemsContainer');
const rowTemplate = document.getElementById('itemRowTemplate');
const addItemBtn = document.getElementById('addItemBtn');
const submitBtn = document.getElementById('submitBtn');
const discountField = document.getElementById('discountField');
const summaryItems = document.getElementById('summaryItems');
const summaryStockAlerts = document.getElementById('summaryStockAlerts');
const summarySubtotal = document.getElementById('summarySubtotal');
const summaryDiscount = document.getElementById('summaryDiscount');
const summaryTotal = document.getElementById('summaryTotal');
const summaryHint = document.getElementById('summaryHint');
const saleForm = document.getElementById('saleForm');

function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
}

function buildProductOptions(selectedId) {
    const options = ['<option value="">Produto...</option>'];

    products.forEach((product) => {
        const selected = String(selectedId || '') === String(product.id) ? 'selected' : '';
        options.push(`<option value="${product.id}" ${selected}>${product.name}</option>`);
    });

    return options.join('');
}

function findProductById(productId) {
    return products.find((product) => String(product.id) === String(productId));
}

function getRowData(row) {
    const quantity = parseFloat(row.querySelector('[data-field="quantity"]').value || '0');
    const unitPrice = parseFloat(row.querySelector('[data-field="unit_price"]').value || '0');
    const catalogItemId = row.querySelector('[data-field="catalog_item_id"]').value;
    const itemName = row.querySelector('[data-field="item_name"]').value.trim();
    const valid = quantity > 0 && (catalogItemId !== '' || itemName !== '');
    const selectedProduct = findProductById(catalogItemId);
    const stockAlert = selectedProduct && quantity > 0 && quantity > Number(selectedProduct.stock || 0);

    return { quantity, unitPrice, catalogItemId, itemName, valid, selectedProduct, stockAlert };
}

function updateRowVisualStatus(row) {
    const data = getRowData(row);
    const statusTag = row.querySelector('[data-field="status"]');
    const stockWarningTag = row.querySelector('[data-field="stock_warning"]');
    const lineTotal = data.quantity * data.unitPrice;
    row.querySelector('[data-field="line_total"]').textContent = formatMoney(lineTotal);

    if (data.stockAlert) {
        stockWarningTag.textContent = `Estoque atual: ${Number(data.selectedProduct.stock || 0).toFixed(3)}`;
        stockWarningTag.classList.remove('hidden');
    } else {
        stockWarningTag.textContent = '';
        stockWarningTag.classList.add('hidden');
    }

    if (data.valid) {
        statusTag.textContent = 'Válido';
        statusTag.className = 'font-semibold text-emerald-700 bg-emerald-100 px-2 py-1 rounded-full';
        row.classList.remove('border-amber-300');
        row.classList.add('border-emerald-200');
        return;
    }

    if (data.catalogItemId !== '' || data.itemName !== '' || data.quantity > 0 || data.unitPrice > 0) {
        statusTag.textContent = 'Incompleto';
        statusTag.className = 'font-semibold text-amber-700 bg-amber-100 px-2 py-1 rounded-full';
        row.classList.remove('border-emerald-200');
        row.classList.add('border-amber-300');
        return;
    }

    statusTag.textContent = 'Vazio';
    statusTag.className = 'font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded-full';
    row.classList.remove('border-emerald-200', 'border-amber-300');
}

function updateSummary() {
    const rows = Array.from(itemsContainer.querySelectorAll('.item-row'));
    let validItems = 0;
    let stockAlerts = 0;
    let subtotal = 0;

    rows.forEach((row) => {
        const data = getRowData(row);
        if (data.valid) {
            validItems += 1;
            subtotal += data.quantity * data.unitPrice;
        }
        if (data.stockAlert) {
            stockAlerts += 1;
        }
    });

    const discount = Math.max(0, parseFloat(discountField?.value || '0') || 0);
    const total = Math.max(0, subtotal - discount);

    summaryItems.textContent = String(validItems);
    summaryStockAlerts.textContent = String(stockAlerts);
    summarySubtotal.textContent = formatMoney(subtotal);
    summaryDiscount.textContent = formatMoney(discount);
    summaryTotal.textContent = formatMoney(total);

    const canSubmit = validItems > 0;
    submitBtn.disabled = !canSubmit;
    summaryHint.classList.toggle('hidden', canSubmit);
}

function bindRowEvents(row) {
    const productField = row.querySelector('[data-field="catalog_item_id"]');

    row.querySelectorAll('input, select').forEach((field) => {
        field.addEventListener('input', () => {
            updateRowVisualStatus(row);
            updateSummary();
            ensureTrailingEmptyRow();
        });
        field.addEventListener('change', () => {
            updateRowVisualStatus(row);
            updateSummary();
            ensureTrailingEmptyRow();
        });
    });

    productField.addEventListener('change', () => {
        const selected = findProductById(productField.value);
        if (!selected) {
            return;
        }

        row.querySelector('[data-field="item_name"]').value = selected.name;
        row.querySelector('[data-field="item_type"]').value = selected.item_type || 'produto';
        row.querySelector('[data-field="unit_price"]').value = Number(selected.price || 0).toFixed(2);
        const quantityField = row.querySelector('[data-field="quantity"]');
        if (String(quantityField.value || '').trim() === '') {
            quantityField.value = '1';
        }

        updateRowVisualStatus(row);
        updateSummary();
        ensureTrailingEmptyRow();
    });

    row.querySelector('[data-action="duplicate"]').addEventListener('click', () => {
        const data = getRowData(row);
        addRow({
            catalog_item_id: data.catalogItemId,
            item_name: row.querySelector('[data-field="item_name"]').value,
            item_type: row.querySelector('[data-field="item_type"]').value,
            quantity: row.querySelector('[data-field="quantity"]').value,
            unit_price: row.querySelector('[data-field="unit_price"]').value,
        });
    });

    row.querySelector('[data-action="remove"]').addEventListener('click', () => {
        const rows = itemsContainer.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            row.querySelectorAll('input, select').forEach((field) => {
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = '';
                }
            });
            updateRowVisualStatus(row);
            updateSummary();
            return;
        }

        row.remove();
        reindexRows();
        updateSummary();
    });
}

function ensureTrailingEmptyRow() {
    const rows = Array.from(itemsContainer.querySelectorAll('.item-row'));
    if (rows.length === 0) {
        addRow();
        return;
    }

    const lastRow = rows[rows.length - 1];
    const lastData = getRowData(lastRow);
    if (lastData.valid) {
        addRow();
    }
}

function reindexRows() {
    const rows = Array.from(itemsContainer.querySelectorAll('.item-row'));
    rows.forEach((row, index) => {
        row.querySelectorAll('[data-field]').forEach((field) => {
            const key = field.getAttribute('data-field');
            if (['catalog_item_id', 'item_type', 'item_name', 'quantity', 'unit_price'].includes(key)) {
                field.setAttribute('name', `items[${index}][${key}]`);
            }
        });
    });
}

function addRow(item = {}) {
    const fragment = rowTemplate.content.cloneNode(true);
    const row = fragment.querySelector('.item-row');

    const productField = row.querySelector('[data-field="catalog_item_id"]');
    productField.innerHTML = buildProductOptions(item.catalog_item_id || '');
    row.querySelector('[data-field="item_type"]').value = item.item_type || 'produto';
    row.querySelector('[data-field="item_name"]').value = item.item_name || '';
    row.querySelector('[data-field="quantity"]').value = item.quantity ?? '';
    row.querySelector('[data-field="unit_price"]').value = item.unit_price ?? '';

    itemsContainer.appendChild(row);
    bindRowEvents(row);
    reindexRows();
    updateRowVisualStatus(row);
    updateSummary();
}

function disableInvalidRowsBeforeSubmit() {
    const rows = Array.from(itemsContainer.querySelectorAll('.item-row'));
    let validCount = 0;

    rows.forEach((row) => {
        const data = getRowData(row);
        const fields = row.querySelectorAll('input, select');

        if (data.valid) {
            validCount += 1;
            fields.forEach((field) => field.disabled = false);
        } else {
            fields.forEach((field) => field.disabled = true);
        }
    });

    return validCount;
}

addItemBtn.addEventListener('click', () => addRow());
discountField?.addEventListener('input', updateSummary);

saleForm.addEventListener('submit', (event) => {
    const validCount = disableInvalidRowsBeforeSubmit();

    if (validCount <= 0) {
        event.preventDefault();
        summaryHint.classList.remove('hidden');
        summaryHint.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    reindexRows();
});

initialItems.forEach((item) => addRow(item));
ensureTrailingEmptyRow();
</script>
@endsection
