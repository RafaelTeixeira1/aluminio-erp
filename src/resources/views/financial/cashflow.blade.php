@extends('layouts.app')

@section('title', 'Fluxo de Caixa')
@section('page-title', 'Financeiro - Fluxo de Caixa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fluxo de Caixa</h1>
            <p class="mt-1 text-gray-600">Consolide entradas e saidas financeiras com visao de caixa realizado e projetado.</p>
        </div>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            <a href="{{ route('cashflow.exportCsv', request()->query()) }}" class="rounded-lg bg-emerald-100 px-4 py-2 text-center text-emerald-800 transition hover:bg-emerald-200">
                Exportar CSV
            </a>
            <a href="{{ route('cashflow.exportPdf', request()->query()) }}" target="_blank" class="rounded-lg bg-slate-100 px-4 py-2 text-center text-slate-800 transition hover:bg-slate-200">
                Exportar PDF
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
    @endif

    @if(($setupRequired ?? false) === true)
        <div class="p-4 bg-amber-100 border border-amber-300 text-amber-900 rounded-lg">
            Fluxo de caixa ainda nao inicializado neste ambiente. Execute <strong>php artisan migrate</strong> para criar as tabelas financeiras.
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-emerald-500">
            <p class="text-sm text-gray-600">Entradas no periodo</p>
            <p class="text-3xl font-bold text-emerald-700 mt-2">R$ {{ number_format((float) $summary['inflow'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-600">Saidas no periodo</p>
            <p class="text-3xl font-bold text-red-700 mt-2">R$ {{ number_format((float) $summary['outflow'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 {{ (float) $summary['net'] >= 0 ? 'border-blue-500' : 'border-amber-500' }}">
            <p class="text-sm text-gray-600">Saldo realizado</p>
            <p class="text-3xl font-bold {{ (float) $summary['net'] >= 0 ? 'text-blue-700' : 'text-amber-700' }} mt-2">R$ {{ number_format((float) $summary['net'], 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
            <p class="text-sm text-gray-600">Receber em aberto</p>
            <p class="text-3xl font-bold text-indigo-700 mt-2">R$ {{ number_format((float) $summary['receivables_open'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-fuchsia-500">
            <p class="text-sm text-gray-600">Pagar em aberto</p>
            <p class="text-3xl font-bold text-fuchsia-700 mt-2">R$ {{ number_format((float) $summary['payables_open'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 {{ (float) $summary['projected_net'] >= 0 ? 'border-green-500' : 'border-orange-500' }}">
            <p class="text-sm text-gray-600">Saldo projetado</p>
            <p class="text-3xl font-bold {{ (float) $summary['projected_net'] >= 0 ? 'text-green-700' : 'text-orange-700' }} mt-2">R$ {{ number_format((float) $summary['projected_net'], 2, ',', '.') }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('cashflow.index') }}" class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Todos</option>
                    <option value="entrada" {{ ($filters['type'] ?? '') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                    <option value="saida" {{ ($filters['type'] ?? '') === 'saida' ? 'selected' : '' }}>Saida</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Origem</label>
                <input type="text" name="origin_type" value="{{ $filters['origin_type'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="receivable, payable...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Periodo de</label>
                <input type="date" name="period_from" value="{{ $filters['period_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Periodo ate</label>
                <input type="date" name="period_to" value="{{ $filters['period_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Descricao / observacao</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Buscar">
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">Filtrar</button>
                <a href="{{ route('cashflow.index') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-center text-gray-900 transition hover:bg-gray-300">Limpar</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lancamento</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origem</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descricao</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($entries as $entry)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-800">#{{ $entry->id }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($entry->type === 'entrada')
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Entrada</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Saida</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ trim((string) $entry->origin_type) !== '' ? $entry->origin_type : '-' }} {{ $entry->origin_id ? '#'.$entry->origin_id : '' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $entry->description }}
                            @if(!empty($entry->notes))
                                <div class="text-xs text-gray-500">{{ $entry->notes }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right {{ $entry->type === 'entrada' ? 'text-emerald-700' : 'text-red-700' }}">R$ {{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->occurred_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->user?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">Nenhum lancamento encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($entries->hasPages())
        <div>{{ $entries->links() }}</div>
    @endif
</div>
@endsection
