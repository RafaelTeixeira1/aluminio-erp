@extends('layouts.app')

@section('title', 'Estoque')
@section('page-title', 'Gerenciamento de Estoque')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Controle de Estoque</h1>
                <p class="text-gray-600 mt-1">Movimentacoes manuais, consulta de saldos e historico recente</p>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                <p class="text-sm text-gray-600">Total de Produtos</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $summary['total_items'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                <p class="text-sm text-gray-600">Unidades em Estoque</p>
                <p class="text-3xl font-bold text-green-700 mt-2">{{ number_format($summary['total_stock'], 3, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                <p class="text-sm text-gray-600">Estoque Baixo</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $summary['low_stock'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                <p class="text-sm text-gray-600">Sem Estoque</p>
                <p class="text-3xl font-bold text-red-600 mt-2">{{ $summary['out_of_stock'] }}</p>
            </div>
        </div>

        @if(($canManageStock ?? false) === true)
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <form method="POST" action="{{ route('stock.entry') }}" class="bg-white rounded-lg shadow p-4 space-y-3">
                @csrf
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Entrada</h3>
                <select name="catalog_item_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Selecione o produto</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} (saldo {{ number_format((float) $product->stock, 3, ',', '.') }})</option>
                    @endforeach
                </select>
                <input name="quantity" type="number" step="0.001" min="0.001" placeholder="Quantidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                <input name="notes" type="text" maxlength="500" placeholder="Observacao (opcional)" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Registrar Entrada</button>
            </form>

            <form method="POST" action="{{ route('stock.output') }}" class="bg-white rounded-lg shadow p-4 space-y-3">
                @csrf
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Saida Manual</h3>
                <select name="catalog_item_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Selecione o produto</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} (saldo {{ number_format((float) $product->stock, 3, ',', '.') }})</option>
                    @endforeach
                </select>
                <input name="quantity" type="number" step="0.001" min="0.001" placeholder="Quantidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                <input name="notes" type="text" maxlength="500" placeholder="Motivo da saida" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Registrar Saida</button>
            </form>

            <form method="POST" action="{{ route('stock.adjust') }}" class="bg-white rounded-lg shadow p-4 space-y-3">
                @csrf
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Ajuste de Saldo</h3>
                <select name="catalog_item_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Selecione o produto</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} (saldo {{ number_format((float) $product->stock, 3, ',', '.') }})</option>
                    @endforeach
                </select>
                <input name="new_stock" type="number" step="0.001" min="0" placeholder="Novo saldo" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                <input name="notes" type="text" maxlength="500" placeholder="Motivo do ajuste" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <button type="submit" class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">Aplicar Ajuste</button>
            </form>
        </div>
        @else
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            Seu perfil possui acesso de consulta ao estoque. Movimentacoes manuais (entrada, saida e ajuste) estao restritas.
        </div>
        @endif

        <div class="bg-white rounded-lg shadow p-4">
            <form method="GET" action="{{ route('stock.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input name="search" value="{{ $filters['search'] }}" placeholder="Buscar produto..." class="md:col-span-2 px-3 py-2 border border-gray-300 rounded-lg">
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Todos os status</option>
                    <option value="normal" @selected($filters['status'] === 'normal')>Normal</option>
                    <option value="baixo" @selected($filters['status'] === 'baixo')>Baixo</option>
                    <option value="sem_estoque" @selected($filters['status'] === 'sem_estoque')>Sem estoque</option>
                </select>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-black transition">Filtrar</button>
            </form>
            <div class="mt-3">
                <a href="{{ route('stock.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">Limpar filtros de produtos</a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estoque</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minimo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($items as $item)
                        @php
                            $stock = (float) $item->stock;
                            $minimum = (float) $item->stock_minimum;
                            $statusClass = $stock <= 0
                                ? 'bg-red-100 text-red-800'
                                : ($stock <= $minimum ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                            $statusLabel = $stock <= 0 ? 'Sem estoque' : ($stock <= $minimum ? 'Baixo' : 'Normal');
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $item->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ ucfirst($item->item_type) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ number_format($stock, 3, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ number_format($minimum, 3, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum produto encontrado para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div>{{ $items->links() }}</div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Ultimas Movimentacoes</h3>
            </div>

            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <form method="GET" action="{{ route('stock.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <select name="movement_item_id" class="px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Todos os produtos</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((int) ($movementFilters['movement_item_id'] ?? 0) === (int) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <select name="movement_type" class="px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Todos os tipos</option>
                        <option value="entrada" @selected(($movementFilters['movement_type'] ?? '') === 'entrada')>Entrada</option>
                        <option value="saida" @selected(($movementFilters['movement_type'] ?? '') === 'saida')>Saida</option>
                        <option value="ajuste" @selected(($movementFilters['movement_type'] ?? '') === 'ajuste')>Ajuste</option>
                    </select>
                    <input type="date" name="movement_from" value="{{ $movementFilters['movement_from'] ?? '' }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <input type="date" name="movement_to" value="{{ $movementFilters['movement_to'] ?? '' }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-black transition">Filtrar Historico</button>
                </form>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('stock.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">Limpar filtros de historico</a>
                    <a href="{{ route('stock.exportCsv', ['movement_item_id' => $movementFilters['movement_item_id'], 'movement_type' => $movementFilters['movement_type'], 'movement_from' => $movementFilters['movement_from'], 'movement_to' => $movementFilters['movement_to']]) }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-sm text-white hover:bg-emerald-700 transition">Exportar CSV</a>
                </div>
            </div>

            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Antes -> Depois</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observacao</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($movements as $movement)
                        @php
                            $typeClasses = [
                                'entrada' => 'bg-green-100 text-green-800',
                                'saida' => 'bg-blue-100 text-blue-800',
                                'ajuste' => 'bg-orange-100 text-orange-800',
                            ];
                            $typeClass = $typeClasses[$movement->movement_type] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ optional($movement->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $movement->catalogItem?->name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $typeClass }}">{{ ucfirst($movement->movement_type) }}</span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ number_format((float) $movement->quantity, 3, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ number_format((float) $movement->stock_before, 3, ',', '.') }} -> {{ number_format((float) $movement->stock_after, 3, ',', '.') }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $movement->notes ?: '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $movement->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Nenhuma movimentacao registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($movements->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">{{ $movements->links() }}</div>
            @endif
        </div>
    </div>
@endsection
