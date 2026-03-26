@extends('layouts.app')

@section('title', 'Relatórios')
@section('page-title', 'Central de Relatórios')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
                @if(($can_view_financial ?? false) === true)
                    <p class="text-gray-600 mt-1">Acompanhe indicadores de vendas, estoque e orçamentos</p>
                @else
                    <p class="text-gray-600 mt-1">Painel operacional focado em estoque e produtos</p>
                @endif
            </div>
            <a href="{{ route('reports.exportPdf', request()->query()) }}" target="_blank" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Exportar PDF
            </a>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="bg-white rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                    <select name="period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="30d" {{ ($filters['period'] ?? '30d') === '30d' ? 'selected' : '' }}>Últimos 30 dias</option>
                        <option value="this_month" {{ ($filters['period'] ?? '') === 'this_month' ? 'selected' : '' }}>Este mês</option>
                        <option value="last_month" {{ ($filters['period'] ?? '') === 'last_month' ? 'selected' : '' }}>Mês anterior</option>
                        <option value="this_year" {{ ($filters['period'] ?? '') === 'this_year' ? 'selected' : '' }}>Este ano</option>
                        <option value="custom" {{ ($filters['period'] ?? '') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">De</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Até</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach(($categories ?? collect()) as $category)
                            <option value="{{ $category->id }}" {{ (int) ($filters['category_id'] ?? 0) === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="confirmada" {{ ($filters['status'] ?? '') === 'confirmada' ? 'selected' : '' }}>Confirmadas</option>
                        <option value="pendente" {{ ($filters['status'] ?? '') === 'pendente' ? 'selected' : '' }}>Pendentes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                    <div class="flex gap-2">
                        <button type="submit" class="w-full bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Aplicar</button>
                        <a href="{{ route('reports.index') }}" class="w-full text-center bg-slate-100 text-slate-800 px-4 py-2 rounded-lg hover:bg-slate-200 transition">Limpar</a>
                    </div>
                </div>
            </div>
        </form>

        @if(($can_view_financial ?? false) === true)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                    <p class="text-sm text-gray-600">Receita no Período</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">R$ {{ number_format($metrics['revenue'], 2, ',', '.') }}</p>
                    <p class="text-xs text-green-600 mt-1">Últimos 30 dias</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-600">Vendas Fechadas</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $metrics['sales_count'] }}</p>
                    <p class="text-xs text-blue-600 mt-1">Total de vendas no período</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                    <p class="text-sm text-gray-600">Conversão de Orçamentos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $metrics['conversion_rate'] }}%</p>
                    <p class="text-xs text-yellow-600 mt-1">Com base no período selecionado</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                    <p class="text-sm text-gray-600">Itens Críticos em Estoque</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ $metrics['low_stock_count'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Repor em até 5 dias</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-600">Produtos Ativos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ (int) ($metrics['active_products'] ?? 0) }}</p>
                    <p class="text-xs text-indigo-600 mt-1">Itens disponíveis no catálogo</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                    <p class="text-sm text-gray-600">Itens Críticos em Estoque</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ (int) ($metrics['low_stock_count'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Abaixo do estoque mínimo</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-rose-500">
                    <p class="text-sm text-gray-600">Sem Estoque</p>
                    <p class="text-3xl font-bold text-rose-600 mt-2">{{ (int) ($metrics['out_of_stock_count'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Reposição imediata recomendada</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-600">Categorias Ativas</p>
                    <p class="text-3xl font-bold text-emerald-700 mt-2">{{ (int) ($metrics['categories_count'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Organização atual do catálogo</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                @if(($can_view_financial ?? false) === true)
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Produtos (Receita)</h3>
                    <div class="space-y-4">
                        @php($maxTopValue = (float) ($top_products->max('total_value') ?? 0))
                        @forelse ($top_products as $product)
                            @php($width = $maxTopValue > 0 ? max(8, (int) round(((float) $product->total_value / $maxTopValue) * 100)) : 8)
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-700">{{ $product->item_name }}</span>
                                    <span class="font-semibold text-gray-900">R$ {{ number_format((float) $product->total_value, 2, ',', '.') }}</span>
                                </div>
                                <svg viewBox="0 0 100 8" class="w-full h-2" aria-hidden="true">
                                    <rect x="0" y="0" width="100" height="8" rx="4" fill="#e5e7eb"></rect>
                                    <rect x="0" y="0" width="{{ $width }}" height="8" rx="4" fill="#2563eb"></rect>
                                </svg>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Sem dados de vendas para o período.</p>
                        @endforelse
                    </div>
                @else
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Produtos para Reposição</h3>
                    <div class="space-y-3">
                        @forelse ($top_products as $product)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-700">{{ $product->name }}</span>
                                <span class="text-sm text-gray-600">{{ number_format((float) $product->stock, 3, ',', '.') }} / min {{ number_format((float) $product->stock_minimum, 3, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Sem itens para reposição no momento.</p>
                        @endforelse
                    </div>
                @endif
            </div>

            @if(($can_view_financial ?? false) === true)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumo por Status</h3>
                    <div class="space-y-3">
                        @forelse ($status_summary as $status)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-700">{{ ucfirst((string) $status->status) }} ({{ (int) $status->count }})</span>
                                <span class="font-semibold text-gray-900">R$ {{ number_format((float) $status->total_value, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Sem dados de status no período.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Observação</h3>
                    <p class="text-sm text-gray-600">Para o perfil de vendedor, os relatórios exibem apenas dados operacionais de produtos e estoque. Indicadores financeiros permanecem restritos ao perfil administrativo.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
