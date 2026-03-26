@extends('layouts.app')

@section('title', 'Desenhos')
@section('page-title', 'Desenhos Tecnicos')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Desenhos de Esquadrias</h1>
            <p class="text-gray-600 mt-1">Rascunhe janelas, portas e medidas para apoiar o atendimento comercial.</p>
        </div>
        <a href="{{ route('designSketches.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Novo desenho</a>
    </div>

    @if (session('success'))
        <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
    @endif

    @if(($setupRequired ?? false) === true)
        <div class="p-4 bg-amber-100 border border-amber-300 text-amber-900 rounded-lg">
            Modulo de desenhos ainda nao inicializado. Execute <strong>php artisan migrate</strong>.
        </div>
    @endif

    @if(($quoteId ?? null) !== null)
        <div class="p-4 bg-indigo-100 border border-indigo-300 text-indigo-900 rounded-lg">
            Filtrando desenhos do orcamento #{{ $quoteId }}.
        </div>
    @endif

    <form method="GET" action="{{ route('designSketches.index') }}" class="bg-white rounded-lg shadow p-4">
        @if(($quoteId ?? null) !== null)
            <input type="hidden" name="quote_id" value="{{ $quoteId }}">
        @endif
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ $search }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Titulo, observacao ou numero do orcamento">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Filtrar</button>
                <a href="{{ route('designSketches.index', ($quoteId ?? null) !== null ? ['quote_id' => $quoteId] : []) }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Limpar</a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($sketches as $sketch)
            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                <div class="h-44 bg-gray-100 flex items-center justify-center">
                    @if(!empty($sketch->preview_png))
                        <img src="{{ $sketch->preview_png }}" alt="Preview do desenho" class="h-full w-full object-contain">
                    @else
                        <span class="text-sm text-gray-500">Sem preview</span>
                    @endif
                </div>
                <div class="p-4 space-y-2">
                    <h2 class="text-base font-semibold text-gray-900">{{ $sketch->title }}</h2>
                    <div class="text-sm text-gray-600">
                        @if($sketch->quote_id)
                            Orcamento #{{ $sketch->quote_id }} - {{ $sketch->quote?->client?->name ?? 'Sem cliente' }}
                        @else
                            Sem orcamento vinculado
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $sketch->width_mm ? number_format((float) $sketch->width_mm, 0, ',', '.') : '-' }} mm x {{ $sketch->height_mm ? number_format((float) $sketch->height_mm, 0, ',', '.') : '-' }} mm
                    </div>
                    @if(!empty($sketch->notes))
                        <p class="text-xs text-gray-600 line-clamp-2">{{ $sketch->notes }}</p>
                    @endif
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('designSketches.edit', $sketch) }}" class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">Abrir</a>
                        <form action="{{ route('designSketches.destroy', $sketch) }}" method="POST" onsubmit="return confirm('Excluir este desenho?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm bg-red-100 text-red-700 px-3 py-1.5 rounded hover:bg-red-200">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 xl:col-span-3 bg-white rounded-lg shadow border border-dashed border-gray-300 p-10 text-center text-gray-500">
                Nenhum desenho encontrado.
            </div>
        @endforelse
    </div>

    @if($sketches->hasPages())
        <div>{{ $sketches->links() }}</div>
    @endif
</div>
@endsection
