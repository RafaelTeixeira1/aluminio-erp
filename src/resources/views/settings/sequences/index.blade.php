@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Configuração de Sequências</h1>
                <p class="mt-2 text-gray-600">Gerencie a numeração automática de documentos (Compras, Vendas, Orçamentos, NFe)</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6">
            @foreach ($sequences as $sequence)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">{{ $sequence->description }}</h3>
                                <p class="text-sm text-gray-500 mt-1">Código: <code class="bg-gray-100 px-2 py-1 rounded">{{ $sequence->code }}</code></p>
                            </div>
                            <a href="{{ route('settings.sequences.show', $sequence) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                Ver Detalhes
                            </a>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200">
                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Prefixo</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">
                                    @if ($sequence->prefix)
                                        <code class="bg-gray-100 px-2 py-1 rounded">{{ $sequence->prefix }}</code>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Próximo Número</p>
                                <p class="mt-1 text-lg font-semibold text-blue-600">{{ $sequence->next_number }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Padrão</p>
                                <p class="mt-1 text-sm text-gray-900 font-mono">{{ $sequence->pattern }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Reset</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    @switch($sequence->reset_frequency)
                                        @case('annual')
                                            Anual
                                            @break
                                        @case('monthly')
                                            Mensal
                                            @break
                                        @default
                                            Nunca
                                    @endswitch
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200 flex gap-2">
                            <a href="{{ route('settings.sequences.edit', $sequence) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Editar</a>
                            <form method="POST" action="{{ route('settings.sequences.reset', $sequence) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-amber-600 hover:text-amber-700 text-sm font-medium" onclick="return confirm('Deseja resetar este contador para 1?')">Reset</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">Informações de Ajuda</h3>
            <div class="text-sm text-blue-800 space-y-2">
                <p><strong>Placeholders disponíveis no padrão:</strong></p>
                <ul class="list-disc list-inside ml-2 space-y-1">
                    <li><code>P</code> - Substituído pelo prefixo</li>
                    <li><code>%Y</code> - Ano completo (2026)</li>
                    <li><code>%y</code> - Ano abreviado (26)</li>
                    <li><code>%m</code> - Mês (03)</li>
                    <li><code>%d</code> - Dia (26)</li>
                    <li><code>%06d</code> - Número com 6 dígitos de padding (000001)</li>
                </ul>
                <p class="mt-4"><strong>Exemplos:</strong></p>
                <ul class="list-disc list-inside ml-2 space-y-1">
                    <li>Padrão <code>P%06d</code> com prefixo <code>PC-</code> gera: <strong>PC-000001</strong></li>
                    <li>Padrão <code>P%Y%06d</code> com prefixo <code>V-</code> gera: <strong>V-2026000001</strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
