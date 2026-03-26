@extends('layouts.app')

@section('title', 'Contas a Receber')
@section('page-title', 'Financeiro - Contas a Receber')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Contas a Receber</h1>
            <p class="text-gray-600 mt-1">Acompanhe titulos gerados pelas vendas e registre baixas parciais ou totais.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('receivables.exportCsv', request()->query()) }}" class="bg-emerald-100 text-emerald-800 px-4 py-2 rounded-lg hover:bg-emerald-200 transition">
                Exportar CSV
            </a>
            <a href="{{ route('receivables.exportPdf', request()->query()) }}" target="_blank" class="bg-slate-100 text-slate-800 px-4 py-2 rounded-lg hover:bg-slate-200 transition">
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
            Modulo financeiro ainda nao inicializado neste ambiente. Execute <strong>php artisan migrate</strong> para criar a tabela de contas a receber.
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
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
            <p class="text-sm text-gray-600">Saldo em aberto</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ {{ number_format((float) $summary['open_balance'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-600">Baixado hoje</p>
            <p class="text-3xl font-bold text-green-700 mt-2">R$ {{ number_format((float) $summary['settled_today'], 2, ',', '.') }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('receivables.index') }}" class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cliente / venda</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Nome do cliente ou numero da venda">
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
                <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento de</label>
                <input type="date" name="due_from" value="{{ $filters['due_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento ate</label>
                <input type="date" name="due_to" value="{{ $filters['due_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Saldo minimo (R$)</label>
                <input type="number" step="0.01" min="0" name="min_balance" value="{{ $filters['min_balance'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Saldo maximo (R$)</label>
                <input type="number" step="0.01" min="0" name="max_balance" value="{{ $filters['max_balance'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Filtrar</button>
                <a href="{{ route('receivables.index') }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Limpar</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titulo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parcela</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pago</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acoes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($receivables as $receivable)
                    @php
                        $isOverdue = in_array($receivable->status, ['aberto', 'parcial'], true)
                            && $receivable->due_date !== null
                            && $receivable->due_date->lt(now()->startOfDay());

                        $statusLabel = $isOverdue ? 'vencido' : $receivable->status;
                        $statusClass = match ($statusLabel) {
                            'quitado' => 'bg-green-100 text-green-800',
                            'parcial' => 'bg-amber-100 text-amber-800',
                            'vencido' => 'bg-red-100 text-red-800',
                            'cancelado' => 'bg-gray-200 text-gray-700',
                            default => 'bg-blue-100 text-blue-800',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-800">
                            #{{ $receivable->id }}
                            <div class="text-xs text-gray-500">Venda #{{ $receivable->sale_id ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $receivable->client?->name ?? 'Sem cliente' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $receivable->installment_number }}/{{ $receivable->installment_count }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ ucfirst((string) $statusLabel) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $receivable->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">R$ {{ number_format((float) $receivable->amount_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700">R$ {{ number_format((float) $receivable->amount_paid, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">R$ {{ number_format((float) $receivable->balance, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm space-y-2">
                            @if($receivable->status === 'aberto' && (float) $receivable->amount_paid <= 0 && (int) $receivable->installment_count === 1)
                                <form action="{{ route('receivables.split', $receivable) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                    @csrf
                                    <input type="number" name="installments" min="2" max="24" value="2" class="px-2 py-1 border border-gray-300 rounded text-xs" title="Quantidade de parcelas">
                                    <input type="date" name="first_due_date" value="{{ $receivable->due_date?->format('Y-m-d') ?? now()->toDateString() }}" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                    <div class="flex gap-2">
                                        <input type="number" name="interval_days" min="1" max="90" value="30" class="w-20 px-2 py-1 border border-gray-300 rounded text-xs" title="Intervalo em dias">
                                        <button type="submit" class="bg-indigo-600 text-white px-2 py-1 rounded text-xs hover:bg-indigo-700">Parcelar</button>
                                    </div>
                                </form>
                            @endif

                            <form action="{{ route('receivables.update', $receivable) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @csrf
                                @method('PUT')
                                <input type="date" name="due_date" value="{{ $receivable->due_date?->format('Y-m-d') ?? '' }}" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                <button type="submit" class="bg-slate-600 text-white px-2 py-1 rounded text-xs hover:bg-slate-700">Salvar vencimento</button>
                                <input type="text" name="notes" value="{{ $receivable->notes ?? '' }}" placeholder="Observacao" class="md:col-span-2 px-2 py-1 border border-gray-300 rounded text-xs">
                            </form>

                            @if(in_array($receivable->status, ['aberto', 'parcial'], true))
                                <form action="{{ route('receivables.settle', $receivable) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                    @csrf
                                    <input type="number" step="0.01" min="0.01" max="{{ (float) $receivable->balance }}" name="amount" placeholder="Valor" class="px-2 py-1 border border-gray-300 rounded text-xs" required>
                                    <input type="date" name="paid_at" value="{{ now()->toDateString() }}" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                    <button type="submit" class="bg-emerald-600 text-white px-2 py-1 rounded text-xs hover:bg-emerald-700">Baixar</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-500">Sem ação disponível</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-500">Nenhum titulo encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($receivables->hasPages())
        <div>{{ $receivables->links() }}</div>
    @endif
</div>
@endsection
