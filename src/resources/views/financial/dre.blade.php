@extends('layouts.app')

@section('title', 'DRE Simplificado')
@section('page-title', 'Financeiro - DRE Simplificado')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">DRE Simplificado</h1>
            <p class="text-gray-600 mt-1">Demonstrativo gerencial com receita, despesas e lucro no periodo selecionado.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('financialStatement.exportCsv', request()->query()) }}" class="bg-emerald-100 text-emerald-800 px-4 py-2 rounded-lg hover:bg-emerald-200 transition">
                Exportar CSV
            </a>
            <a href="{{ route('financialStatement.exportPdf', request()->query()) }}" target="_blank" class="bg-slate-100 text-slate-800 px-4 py-2 rounded-lg hover:bg-slate-200 transition">
                Exportar PDF
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
    @endif

    @if(($setupRequired ?? false) === true)
        <div class="p-4 bg-amber-100 border border-amber-300 text-amber-900 rounded-lg">
            DRE ainda nao inicializado neste ambiente. Execute <strong>php artisan migrate</strong> para criar as tabelas financeiras.
        </div>
    @endif

    <form method="GET" action="{{ route('financialStatement.index') }}" class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Periodo de</label>
                <input type="date" name="period_from" value="{{ $filters['period_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Periodo ate</label>
                <input type="date" name="period_to" value="{{ $filters['period_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Buscar em descricao/obs</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Buscar">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Filtrar</button>
                <a href="{{ route('financialStatement.index') }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Limpar</a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-emerald-500">
            <p class="text-sm text-gray-600">Receita Bruta</p>
            <p class="text-3xl font-bold text-emerald-700 mt-2">R$ {{ number_format((float) $summary['gross_revenue'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-600">Despesas Operacionais</p>
            <p class="text-3xl font-bold text-red-700 mt-2">R$ {{ number_format((float) $summary['operational_expenses'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 {{ (float) $summary['net_profit'] >= 0 ? 'border-blue-500' : 'border-amber-500' }}">
            <p class="text-sm text-gray-600">Lucro Liquido</p>
            <p class="text-3xl font-bold {{ (float) $summary['net_profit'] >= 0 ? 'text-blue-700' : 'text-amber-700' }} mt-2">R$ {{ number_format((float) $summary['net_profit'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 {{ (float) $summary['profit_margin'] >= 0 ? 'border-indigo-500' : 'border-orange-500' }}">
            <p class="text-sm text-gray-600">Margem Liquida</p>
            <p class="text-3xl font-bold {{ (float) $summary['profit_margin'] >= 0 ? 'text-indigo-700' : 'text-orange-700' }} mt-2">{{ number_format((float) $summary['profit_margin'], 2, ',', '.') }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Receitas por origem</h3>
            <div class="space-y-3">
                @forelse($incomeByOrigin as $row)
                    <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg">
                        <span class="text-gray-700">{{ ucfirst((string) $row->origin) }}</span>
                        <span class="font-semibold text-emerald-800">R$ {{ number_format((float) $row->total, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Sem receitas no periodo.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Despesas por categoria (centro de custo)</h3>
            <div class="space-y-3">
                @forelse($expensesByCategory as $row)
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                        <span class="text-gray-700">{{ ucfirst((string) $row->category) }}</span>
                        <span class="font-semibold text-red-800">R$ {{ number_format((float) $row->total, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Sem despesas no periodo.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Lancamentos considerados no DRE</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lancamento</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descricao</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origem</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
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
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $entry->description }}
                            @if(!empty($entry->notes))
                                <div class="text-xs text-gray-500">{{ $entry->notes }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ trim((string) $entry->origin_type) !== '' ? $entry->origin_type : '-' }} {{ $entry->origin_id ? '#'.$entry->origin_id : '' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->occurred_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right {{ $entry->type === 'entrada' ? 'text-emerald-700' : 'text-red-700' }}">R$ {{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">Nenhum lancamento encontrado.</td>
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
