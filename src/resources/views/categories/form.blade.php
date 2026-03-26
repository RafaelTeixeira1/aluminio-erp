@extends('layouts.app')

@section('title', $category ? 'Editar Categoria' : 'Nova Categoria')
@section('page-title', $category ? 'Editar Categoria' : 'Nova Categoria')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">{{ $category ? 'Editar Categoria' : 'Nova Categoria' }}</h1>

    <form action="{{ $category ? route('categories.update', $category) : route('categories.store') }}" method="POST" class="space-y-4">
        @csrf
        @if($category)
            @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Categoria</label>
            <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required
                class="w-full px-4 py-2 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="active" value="1" {{ old('active', $category->active ?? true) ? 'checked' : '' }} class="rounded">
                <span class="ml-2 text-sm text-gray-700">Ativa</span>
            </label>
        </div>

        <div class="flex gap-2 pt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                {{ $category ? 'Atualizar' : 'Criar' }}
            </button>
            <a href="{{ route('categories.crud') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
