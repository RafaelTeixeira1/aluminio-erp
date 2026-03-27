@extends('layouts.app')

@section('title', 'Vendas')
@section('page-title', 'Gerenciar Vendas')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Vendas</h1>
        <p class="mt-1 text-gray-600">Controle, edite itens e confirme para baixar estoque</p>
    </div>
    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
        <a href="{{ route('sales.exportCsv', request()->query()) }}" class="rounded-lg bg-emerald-100 px-4 py-2 text-center text-emerald-800 transition hover:bg-emerald-200">
            Exportar CSV
        </a>
        <a href="{{ route('sales.exportPdf', request()->query()) }}" target="_blank" class="rounded-lg bg-slate-100 px-4 py-2 text-center text-slate-800 transition hover:bg-slate-200">
            Exportar PDF
        </a>
        <a href="{{ route('sales.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-center text-white transition hover:bg-blue-700">+ Nova Venda</a>
    </div>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
@endif

<form method="GET" action="{{ route('sales.index') }}" class="mb-4 bg-white rounded-lg shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome do cliente" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">Todos</option>
                <option value="pendente" {{ ($filters['status'] ?? '') === 'pendente' ? 'selected' : '' }}>Pendente</option>
                <option value="confirmada" {{ ($filters['status'] ?? '') === 'confirmada' ? 'selected' : '' }}>Confirmada</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Valor mínimo (R$)</label>
            <input type="number" step="0.01" min="0" name="min_total" value="{{ $filters['min_total'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0,00">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Valor máximo (R$)</label>
            <input type="number" step="0.01" min="0" name="max_total" value="{{ $filters['max_total'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0,00">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Ordenação</label>
            <select name="sort_by" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="created_at" {{ ($filters['sort_by'] ?? 'created_at') === 'created_at' ? 'selected' : '' }}>Data de criação</option>
                <option value="total" {{ ($filters['sort_by'] ?? '') === 'total' ? 'selected' : '' }}>Valor total</option>
                <option value="status" {{ ($filters['sort_by'] ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                <option value="id" {{ ($filters['sort_by'] ?? '') === 'id' ? 'selected' : '' }}>Número da venda</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Direção</label>
            <select name="sort_dir" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="desc" {{ ($filters['sort_dir'] ?? 'desc') === 'desc' ? 'selected' : '' }}>Decrescente</option>
                <option value="asc" {{ ($filters['sort_dir'] ?? '') === 'asc' ? 'selected' : '' }}>Crescente</option>
            </select>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Filtrar</button>
            <a href="{{ route('sales.index') }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Limpar</a>
        </div>
    </div>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Itens</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acoes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $sale->id }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $sale->client->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $sale->status === 'confirmada' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ ucfirst($sale->status) }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-right text-gray-700">{{ $sale->items->count() }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-right text-gray-900">R$ {{ number_format((float) $sale->total, 2, ',', '.') }}</td>
                    <td class="px-6 py-4 text-sm text-right table-actions">
                        <a href="{{ route('sales.printPreview', $sale) }}" target="_blank" class="text-slate-600 hover:text-slate-900">Impressao</a>
                        @if($sale->status !== 'confirmada')
                            <a href="{{ route('sales.edit', $sale) }}" class="text-blue-600 hover:text-blue-900">Editar Itens</a>
                            <button
                                type="button"
                                class="text-green-600 hover:text-green-900"
                                data-action="open-confirm-modal"
                                data-sale-id="{{ $sale->id }}"
                                data-sale-total="{{ number_format((float) $sale->total, 2, ',', '.') }}"
                                data-sale-client="{{ $sale->client->name ?? 'Sem cliente' }}"
                            >
                                Confirmar
                            </button>
                        @else
                            <span class="text-gray-500">Concluida</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Nenhuma venda encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($sales->hasPages())
    <div class="mt-4">{{ $sales->links() }}</div>
@endif

<div id="confirmSaleModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="w-full max-w-xl bg-white rounded-xl shadow-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Confirmar Venda</h3>
            <button type="button" id="closeConfirmSaleModal" class="text-gray-500 hover:text-gray-700">Fechar</button>
        </div>

        <form id="confirmSaleForm" method="POST" class="p-5 space-y-4">
            @csrf

            <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Venda</span>
                    <strong id="confirmSaleNumber" class="text-gray-900">-</strong>
                </div>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-gray-600">Cliente</span>
                    <strong id="confirmSaleClient" class="text-gray-900">-</strong>
                </div>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-gray-600">Total</span>
                    <strong id="confirmSaleTotal" class="text-gray-900">-</strong>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Parcelas</label>
                    <input id="confirmInstallments" type="number" name="installments" min="1" max="24" value="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Primeiro vencimento</label>
                    <input id="confirmFirstDueDate" type="date" name="first_due_date" value="{{ now()->toDateString() }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" disabled>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Intervalo (dias)</label>
                    <input id="confirmIntervalDays" type="number" name="interval_days" min="1" max="90" value="30" class="w-full px-3 py-2 border border-gray-300 rounded-lg" disabled>
                </div>
            </div>

            <p id="confirmHint" class="text-xs text-gray-500">Com 1 parcela, será gerado um único título no financeiro.</p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                <button type="button" id="cancelConfirmSale" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Confirmar venda</button>
            </div>
        </form>
    </div>
</div>

<script>
const confirmSaleModal = document.getElementById('confirmSaleModal');
const confirmSaleForm = document.getElementById('confirmSaleForm');
const confirmInstallments = document.getElementById('confirmInstallments');
const confirmFirstDueDate = document.getElementById('confirmFirstDueDate');
const confirmIntervalDays = document.getElementById('confirmIntervalDays');
const confirmHint = document.getElementById('confirmHint');
const confirmSaleNumber = document.getElementById('confirmSaleNumber');
const confirmSaleClient = document.getElementById('confirmSaleClient');
const confirmSaleTotal = document.getElementById('confirmSaleTotal');

function syncFinancialFields() {
    const installments = Number(confirmInstallments.value || 1);
    const isSingle = installments <= 1;

    confirmFirstDueDate.disabled = isSingle;
    confirmIntervalDays.disabled = isSingle;

    if (isSingle) {
        confirmHint.textContent = 'Com 1 parcela, sera gerado um unico titulo no financeiro.';
    } else {
        confirmHint.textContent = `Parcelamento em ${installments}x com vencimentos em sequencia.`;
    }
}

function openConfirmModal(button) {
    const saleId = button.getAttribute('data-sale-id');
    const client = button.getAttribute('data-sale-client') || '-';
    const total = button.getAttribute('data-sale-total') || '-';

    confirmSaleForm.action = `/vendas/${saleId}/confirmar`;
    confirmSaleNumber.textContent = `#${saleId}`;
    confirmSaleClient.textContent = client;
    confirmSaleTotal.textContent = `R$ ${total}`;

    confirmInstallments.value = '1';
    syncFinancialFields();

    confirmSaleModal.classList.remove('hidden');
}

function closeConfirmModal() {
    confirmSaleModal.classList.add('hidden');
}

document.querySelectorAll('[data-action="open-confirm-modal"]').forEach((button) => {
    button.addEventListener('click', () => openConfirmModal(button));
});

document.getElementById('closeConfirmSaleModal')?.addEventListener('click', closeConfirmModal);
document.getElementById('cancelConfirmSale')?.addEventListener('click', closeConfirmModal);

confirmSaleModal?.addEventListener('click', (event) => {
    if (event.target === confirmSaleModal) {
        closeConfirmModal();
    }
});

confirmInstallments?.addEventListener('input', syncFinancialFields);
</script>
@endsection
