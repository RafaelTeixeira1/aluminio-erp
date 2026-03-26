@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <a href="{{ route('settings.sequences.index') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Voltar
                </a>
                <h1 class="text-3xl font-bold text-gray-900">{{ $sequence->description }}</h1>
                <p class="mt-2 text-gray-600">Código: <code class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $sequence->code }}</code></p>
            </div>
            <a href="{{ route('settings.sequences.edit', $sequence) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar
            </a>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Configurações --> 
            <div class="lg:col-span-1 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Configuração</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Prefixo</label>
                        <div class="mt-1 flex items-center">
                            <code class="bg-gray-100 px-3 py-2 rounded text-sm font-mono text-gray-900 flex-1">{{ $sequence->prefix ?: '(nenhum)' }}</code>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Padrão</label>
                        <div class="mt-1">
                            <code class="bg-gray-100 px-3 py-2 rounded text-sm font-mono text-gray-900 block break-all">{{ $sequence->pattern }}</code>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Próximo Número</label>
                        <p class="mt-1 text-2xl font-bold text-blue-600">{{ $sequence->next_number }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Frequência de Reset</label>
                        <p class="mt-1 text-sm text-gray-900">
                            @switch($sequence->reset_frequency)
                                @case('annual')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Anualmente</span>
                                    @break
                                @case('monthly')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Mensalmente</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Nunca</span>
                            @endswitch
                        </p>
                    </div>

                    @if ($sequence->last_reset_at)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Último Reset</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $sequence->last_reset_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.sequences.reset', $sequence) }}" class="pt-2 border-t border-gray-200">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 bg-amber-600 text-white text-sm font-medium rounded hover:bg-amber-700 transition" onclick="return confirm('Deseja resetar este contador para 1?')">
                            Resetar Contador
                        </button>
                    </form>
                </div>
            </div>

            <!-- Histórico -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Histórico de Gerações</h2>

                @if ($history->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900">Número</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900">Documento</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900">Gerado por</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($history as $entry)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 font-mono text-gray-900">
                                            {{ $entry->generated_number }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 text-xs">
                                            @if ($entry->document_type && $entry->document_id)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ $entry->document_type }}#{{ $entry->document_id }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ $entry->generatedBy?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 text-xs">
                                            {{ $entry->generated_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($history->hasPages())
                        <div class="mt-6 border-t pt-4">
                            {{ $history->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-8 w-8 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Nenhuma geração registrada ainda
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Status da Sequência</h3>
            <p class="text-sm text-blue-800">
                Próximo documento será numerado como: 
                <code class="bg-white px-2 py-1 rounded font-mono">{{ $sequence->formatNumber($sequence->next_number) }}</code>
            </p>
        </div>
    </div>
</div>
@endsection
