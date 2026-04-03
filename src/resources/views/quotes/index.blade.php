@extends('layouts.app')

@section('title', 'Orcamentos')
@section('page-title', 'Gerenciar Orcamentos')

@section('content')
@php
    $selectedClientEmail = '';
@endphp
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Orcamentos</h1>
        <p class="mt-1 text-gray-600">Acompanhe status e converta em vendas</p>
    </div>
    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
        <a href="{{ route('quotes.exportCsv', request()->query()) }}" class="rounded-lg bg-emerald-100 px-4 py-2 text-center text-emerald-800 transition hover:bg-emerald-200">
            Exportar CSV
        </a>
        <a href="{{ route('quotes.exportPdf', request()->query()) }}" target="_blank" class="rounded-lg bg-slate-100 px-4 py-2 text-center text-slate-800 transition hover:bg-slate-200">
            Exportar PDF
        </a>
        <a href="{{ route('quotes.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-center text-white transition hover:bg-blue-700">
            + Novo Orcamento
        </a>
    </div>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('quotes.index') }}" class="mb-4 bg-white rounded-lg shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-8 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome do cliente" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">Todos</option>
                @foreach(['aberto', 'aprovado', 'convertido', 'cancelado', 'expirado'] as $statusOption)
                    <option value="{{ $statusOption }}" {{ ($filters['status'] ?? '') === $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Validade de</label>
            <input type="date" name="valid_from" value="{{ $filters['valid_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Validade até</label>
            <input type="date" name="valid_to" value="{{ $filters['valid_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
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
                <option value="valid_until" {{ ($filters['sort_by'] ?? '') === 'valid_until' ? 'selected' : '' }}>Validade</option>
                <option value="total" {{ ($filters['sort_by'] ?? '') === 'total' ? 'selected' : '' }}>Valor total</option>
                <option value="status" {{ ($filters['sort_by'] ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                <option value="id" {{ ($filters['sort_by'] ?? '') === 'id' ? 'selected' : '' }}>Numero do orçamento</option>
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
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">Filtrar</button>
            <a href="{{ route('quotes.index') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-center text-gray-900 transition hover:bg-gray-300">Limpar</a>
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validade</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acoes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($quotes as $quote)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $quote->id }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        @if($quote->client)
                            {{ $quote->client->name }}
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                Orçamento rápido
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $quote->status === 'aberto' ? 'bg-blue-100 text-blue-800' : ($quote->status === 'convertido' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst($quote->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '-' }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-right text-gray-900">R$ {{ number_format((float) $quote->total, 2, ',', '.') }}</td>
                    <td class="px-6 py-4 text-sm text-right table-actions">
                        <a href="{{ route('quotes.edit', $quote) }}" class="text-blue-600 hover:text-blue-900">Editar</a>
                        <a href="{{ route('quotes.printPreview', $quote) }}" target="_blank" class="text-slate-600 hover:text-slate-900">Impressao</a>
                        <button type="button" data-action="open-email-modal" data-quote-id="{{ $quote->id }}" data-client-email="{{ e($quote->client?->email ?? '') }}" class="text-amber-600 hover:text-amber-900">Email</button>
                        <form action="{{ route('quotes.duplicate', $quote) }}" method="POST" class="inline" onsubmit="return confirm('Deseja duplicar este orçamento?');">
                            @csrf
                            <button type="submit" class="text-indigo-600 hover:text-indigo-900">Duplicar</button>
                        </form>
                        @if(in_array($quote->status, ['aberto', 'aprovado']))
                            <form action="{{ route('quotes.convert', $quote) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-900">Converter</button>
                            </form>
                        @endif
                        <form action="{{ route('quotes.destroy', $quote) }}" method="POST" class="inline" onsubmit="return confirm('Deseja remover este orcamento?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Nenhum orcamento encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($quotes->hasPages())
    <div class="mt-4">{{ $quotes->links() }}</div>
@endif

<!-- Modal para enviar email -->
<div id="emailModal" class="fixed inset-0 z-50 grid place-items-center bg-black bg-opacity-50" style="display: none;">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Enviar Orçamento por Email</h3>
        
        <form id="emailForm" method="POST">
            @csrf
            <div class="mb-4">
                <label for="recipient_email" class="block text-sm font-medium text-gray-700 mb-2">Email do Destinatário</label>
                <input type="email" id="recipient_email" name="email" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="cliente@example.com">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeEmailModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Enviar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const emailModal = document.getElementById('emailModal');
const emailForm = document.getElementById('emailForm');
const recipientEmail = document.getElementById('recipient_email');

function openEmailModal(quoteId, clientEmail = '') {
    recipientEmail.value = clientEmail;
    emailForm.action = `/orcamentos/${quoteId}/email`;
    emailModal.style.display = 'grid';
}

function closeEmailModal() {
    emailModal.style.display = 'none';
}

document.querySelectorAll('[data-action="open-email-modal"]').forEach((button) => {
    button.addEventListener('click', () => {
        openEmailModal(
            button.getAttribute('data-quote-id'),
            button.getAttribute('data-client-email') || ''
        );
    });
});

emailModal?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEmailModal();
    }
});
</script>
@endsection
