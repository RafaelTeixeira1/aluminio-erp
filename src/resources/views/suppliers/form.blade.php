@extends('layouts.app')

@section('title', $supplier ? 'Editar Fornecedor' : 'Novo Fornecedor')
@section('page-title', $supplier ? 'Editar Fornecedor' : 'Novo Fornecedor')

@section('content')
<div class="max-w-2xl mx-auto">
    <a href="{{ route('suppliers.index') }}" class="text-blue-600 hover:text-blue-900 mb-6 inline-flex items-center">
        ← Voltar para Fornecedores
    </a>

    <div class="bg-white rounded-lg shadow p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">
            {{ $supplier ? 'Editar Fornecedor' : 'Novo Fornecedor' }}
        </h1>

        <form action="{{ $supplier ? route('suppliers.update', $supplier) : route('suppliers.store') }}" method="POST" class="space-y-6">
            @csrf
            @if($supplier)
                @method('PUT')
            @endif

            <!-- Nome -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    Nome <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $supplier?->name) }}" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                       required>
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Documento -->
            <div>
                <label for="document" class="block text-sm font-semibold text-gray-700 mb-2">
                    CNPJ/CPF
                </label>
                <input type="text" id="document" name="document" value="{{ old('document', $supplier?->document) }}" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('document') border-red-500 @enderror"
                       placeholder="00.000.000/0000-00">
                @error('document')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contato -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="contact_person" class="block text-sm font-semibold text-gray-700 mb-2">
                        Pessoa de Contato
                    </label>
                    <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier?->contact_person) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('contact_person') border-red-500 @enderror">
                    @error('contact_person')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                        Telefone
                    </label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $supplier?->phone) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror"
                           placeholder="(11) 99999-9999">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    E-mail
                </label>
                <input type="email" id="email" name="email" value="{{ old('email', $supplier?->email) }}" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                       placeholder="contato@fornecedor.com">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Endereço -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">
                        Endereço
                    </label>
                    <input type="text" id="address" name="address" value="{{ old('address', $supplier?->address) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('address') border-red-500 @enderror"
                           placeholder="Rua, número, complemento">
                    @error('address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">
                        Cidade
                    </label>
                    <input type="text" id="city" name="city" value="{{ old('city', $supplier?->city) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('city') border-red-500 @enderror">
                    @error('city')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Notas -->
            <div>
                <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                    Notas
                </label>
                <textarea id="notes" name="notes" rows="4" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror"
                          placeholder="Observações sobre o fornecedor...">{{ old('notes', $supplier?->notes) }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Botões -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                    {{ $supplier ? 'Atualizar' : 'Criar' }} Fornecedor
                </button>
                <a href="{{ route('suppliers.index') }}" class="bg-gray-300 text-gray-900 px-6 py-2 rounded-lg hover:bg-gray-400 transition font-medium">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
