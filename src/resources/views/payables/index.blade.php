@extends('layouts.app')

@section('title', 'Contas a Pagar')
@section('page-title', 'Financeiro - Contas a Pagar')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Contas a Pagar</h1>
            <p class="mt-1 text-gray-600">Lance despesas, acompanhe vencimentos e registre pagamentos parciais ou totais.</p>
        </div>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            <a href="{{ route('payables.exportCsv', request()->query()) }}" class="rounded-lg bg-emerald-100 px-4 py-2 text-center text-emerald-800 transition hover:bg-emerald-200">
                Exportar CSV
            </a>
            <a href="{{ route('payables.exportPdf', request()->query()) }}" target="_blank" class="rounded-lg bg-slate-100 px-4 py-2 text-center text-slate-800 transition hover:bg-slate-200">
                Exportar PDF
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
    @endif

    @if(($setupRequired ?? false) === true)
        <div class="p-4 bg-amber-100 border border-amber-300 text-amber-900 rounded-lg">
            Modulo financeiro ainda nao inicializado neste ambiente. Execute <strong>php artisan migrate</strong> para criar as tabelas financeiras.
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-600">Titulos em aberto</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $summary['open_count'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-500">
            <p class="text-sm text-gray-600">Titulos vencidos</p>
            <p class="text-3xl font-bold text-amber-700 mt-2">{{ $summary['overdue_count'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-rose-500">
            <p class="text-sm text-gray-600">Saldo a pagar</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ {{ number_format((float) $summary['open_balance'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-600">Pago hoje</p>
            <p class="text-3xl font-bold text-red-700 mt-2">R$ {{ number_format((float) $summary['paid_today'], 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Nova conta a pagar</h2>
        <form action="{{ route('payables.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fornecedor *</label>
                <input type="text" name="vendor_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Nome do fornecedor">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Descricao *</label>
                <input type="text" name="description" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Compra de perfis">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Categoria</label>
                <input type="text" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg" value="geral">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Documento</label>
                <input type="text" name="document_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="NF / boleto">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Total (R$) *</label>
                <input type="number" step="0.01" min="0.01" name="amount_total" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento</label>
                <input type="date" name="due_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Observacoes</label>
                <input type="text" name="notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Observacoes internas">
            </div>
            <div class="md:col-span-2 flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Lancar conta</button>
            </div>
        </form>
    </div>

    <form method="GET" action="{{ route('payables.index') }}" class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-8 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fornecedor / descricao</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Buscar">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Todos</option>
                    @foreach(['aberto', 'parcial', 'quitado', 'cancelado', 'vencido'] as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Categoria</label>
                <input type="text" name="category" value="{{ $filters['category'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento de</label>
                <input type="date" name="due_from" value="{{ $filters['due_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento ate</label>
                <input type="date" name="due_to" value="{{ $filters['due_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Saldo min. (R$)</label>
                <input type="number" step="0.01" min="0" name="min_balance" value="{{ $filters['min_balance'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Saldo max. (R$)</label>
                <input type="number" step="0.01" min="0" name="max_balance" value="{{ $filters['max_balance'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Filtrar</button>
                <a href="{{ route('payables.index') }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Limpar</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titulo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descricao</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pago</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acoes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payables as $payable)
                    @php
                        $isOverdue = in_array($payable->status, ['aberto', 'parcial'], true)
                            && $payable->due_date !== null
                            && $payable->due_date->lt(now()->startOfDay());

                        $statusLabel = $isOverdue ? 'vencido' : $payable->status;
                        $statusClass = match ($statusLabel) {
                            'quitado' => 'bg-green-100 text-green-800',
                            'parcial' => 'bg-amber-100 text-amber-800',
                            'vencido' => 'bg-red-100 text-red-800',
                            'cancelado' => 'bg-gray-200 text-gray-700',
                            default => 'bg-blue-100 text-blue-800',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-800">#{{ $payable->id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $payable->vendor_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $payable->description }}
                            <div class="text-xs text-gray-500">{{ $payable->category }} {{ $payable->document_number ? '• '.$payable->document_number : '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ ucfirst((string) $statusLabel) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $payable->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">R$ {{ number_format((float) $payable->amount_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-red-700">R$ {{ number_format((float) $payable->amount_paid, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">R$ {{ number_format((float) $payable->balance, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm space-y-2">
                            <form action="{{ route('payables.update', $payable) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="vendor_name" value="{{ $payable->vendor_name }}" class="px-2 py-1 border border-gray-300 rounded text-xs" placeholder="Fornecedor">
                                <input type="text" name="description" value="{{ $payable->description }}" class="px-2 py-1 border border-gray-300 rounded text-xs" placeholder="Descricao">
                                <input type="text" name="category" value="{{ $payable->category }}" class="px-2 py-1 border border-gray-300 rounded text-xs" placeholder="Categoria">
                                <input type="text" name="document_number" value="{{ $payable->document_number }}" class="px-2 py-1 border border-gray-300 rounded text-xs" placeholder="Documento">
                                <input type="date" name="due_date" value="{{ $payable->due_date?->format('Y-m-d') ?? '' }}" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                <button type="submit" class="bg-slate-600 text-white px-2 py-1 rounded text-xs hover:bg-slate-700">Salvar dados</button>
                                <input type="text" name="notes" value="{{ $payable->notes ?? '' }}" placeholder="Observacao" class="md:col-span-2 px-2 py-1 border border-gray-300 rounded text-xs">
                            </form>

                            @if(in_array($payable->status, ['aberto', 'parcial'], true))
                                <form action="{{ route('payables.settle', $payable) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                    @csrf
                                    <input type="number" step="0.01" min="0.01" max="{{ (float) $payable->balance }}" name="amount" placeholder="Valor" class="px-2 py-1 border border-gray-300 rounded text-xs" required>
                                    <input type="date" name="paid_at" value="{{ now()->toDateString() }}" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                    <button type="submit" class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">Pagar</button>
                                </form>

                                <form action="{{ route('payables.cancel', $payable) }}" method="POST" class="flex gap-2">
                                    @csrf
                                    <input type="text" name="notes" class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs" placeholder="Motivo do cancelamento (opcional)">
                                    <button type="submit" class="bg-gray-600 text-white px-2 py-1 rounded text-xs hover:bg-gray-700">Cancelar</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-500">Sem acao disponivel</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-500">Nenhuma conta encontrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payables->hasPages())
        <div>{{ $payables->links() }}</div>
    @endif
</div>
@endsection
