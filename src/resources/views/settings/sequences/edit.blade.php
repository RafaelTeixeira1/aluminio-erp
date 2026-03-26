@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('settings.sequences.show', $sequence) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Voltar
        </a>

        <h1 class="text-3xl font-bold text-gray-900 mb-2">Editar Sequência</h1>
        <p class="text-gray-600 mb-8">{{ $sequence->description }}</p>

        <form method="POST" action="{{ route('settings.sequences.update', $sequence) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <input type="text" id="description" name="description" value="{{ old('description', $sequence->description) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                        required>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="prefix" class="block text-sm font-medium text-gray-700 mb-2">
                        Prefixo
                    </label>
                    <input type="text" id="prefix" name="prefix" value="{{ old('prefix', $sequence->prefix) }}"
                        placeholder="Ex: PC-, V-, NF-" maxlength="20"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('prefix') border-red-500 @enderror"
                        required>
                    @error('prefix')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Texto que aparece no início de cada número gerado</p>
                </div>

                <div>
                    <label for="pattern" class="block text-sm font-medium text-gray-700 mb-2">
                        Padrão de Formatação
                    </label>
                    <input type="text" id="pattern" name="pattern" value="{{ old('pattern', $sequence->pattern) }}"
                        placeholder="Ex: P%06d ou P%Y%06d" maxlength="60"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono @error('pattern') border-red-500 @enderror"
                        required>
                    @error('pattern')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    
                    <div class="mt-3 p-4 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs font-semibold text-gray-700 mb-2">Placeholders disponíveis:</p>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li><code class="bg-white px-1 py-0.5 rounded">P</code> → Prefixo</li>
                            <li><code class="bg-white px-1 py-0.5 rounded">%Y</code> → Ano completo (2026)</li>
                            <li><code class="bg-white px-1 py-0.5 rounded">%y</code> → Ano abreviado (26)</li>
                            <li><code class="bg-white px-1 py-0.5 rounded">%m</code> → Mês (03)</li>
                            <li><code class="bg-white px-1 py-0.5 rounded">%d</code> → Dia (26)</li>
                            <li><code class="bg-white px-1 py-0.5 rounded">%06d</code> → Número com 6 dígitos</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <label for="reset_frequency" class="block text-sm font-medium text-gray-700 mb-2">
                        Frequência de Reset
                    </label>
                    <select id="reset_frequency" name="reset_frequency"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('reset_frequency') border-red-500 @enderror"
                        required>
                        <option value="never" @selected(old('reset_frequency', $sequence->reset_frequency) === 'never')>Nunca (contínuo)</option>
                        <option value="annual" @selected(old('reset_frequency', $sequence->reset_frequency) === 'annual')>Anualmente</option>
                        <option value="monthly" @selected(old('reset_frequency', $sequence->reset_frequency) === 'monthly')>Mensalmente</option>
                    </select>
                    @error('reset_frequency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Define quando o contador volta para 1</p>
                </div>

                <div class="p-4 bg-blue-50 border border-blue-200 rounded">
                    <p class="text-sm text-blue-900"><strong>Próximo número:</strong> {{ $sequence->formatNumber($sequence->next_number) }}</p>
                </div>

                <div class="flex gap-4 pt-4 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                        Salvar Alterações
                    </button>
                    <a href="{{ route('settings.sequences.show', $sequence) }}" class="px-6 py-2 bg-gray-200 text-gray-900 font-medium rounded-lg hover:bg-gray-300 transition">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
