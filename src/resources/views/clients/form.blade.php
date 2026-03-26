@extends('layouts.app')

@section('title', $client ? 'Editar Cliente' : 'Novo Cliente')
@section('page-title', $client ? 'Editar Cliente' : 'Novo Cliente')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">{{ $client ? 'Editar Cliente' : 'Novo Cliente' }}</h1>

    <form action="{{ $client ? route('clients.update', $client) : route('clients.store') }}" method="POST" class="space-y-4">
        @csrf
        @if($client)
            @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
            <input type="text" name="name" value="{{ old('name', $client->name ?? '') }}" required
                   class="w-full px-4 py-2 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                <input type="text" name="phone" value="{{ old('phone', $client->phone ?? '') }}" required
                       class="w-full px-4 py-2 border @error('phone') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Documento</label>
                <input type="text" name="document" value="{{ old('document', $client->document ?? '') }}"
                       class="w-full px-4 py-2 border @error('document') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('document')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
            <input type="email" name="email" value="{{ old('email', $client->email ?? '') }}"
                   class="w-full px-4 py-2 border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
            <textarea name="address" rows="3"
                      class="w-full px-4 py-2 border @error('address') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('address', $client->address ?? '') }}</textarea>
            @error('address')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="pt-4 flex items-center gap-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                {{ $client ? 'Atualizar' : 'Salvar' }}
            </button>
            <a href="{{ route('clients.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 transition">Cancelar</a>
        </div>
    </form>
</div>
@endsection
