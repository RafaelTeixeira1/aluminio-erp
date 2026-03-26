@extends('layouts.app')

@section('title', $user ? 'Editar Usuario' : 'Novo Usuario')
@section('page-title', $user ? 'Editar Usuario' : 'Novo Usuario')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">{{ $user ? 'Editar Usuario' : 'Novo Usuario' }}</h1>

    <form action="{{ $user ? route('users.update', $user) : route('users.store') }}" method="POST" class="space-y-4">
        @csrf
        @if($user)
            @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha {{ $user ? '(deixe em branco para manter)' : '' }}</label>
                <input type="password" name="password" {{ $user ? '' : 'required' }} class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Perfil</label>
                <select name="profile" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    @foreach(['admin','vendedor','estoquista','operador'] as $profile)
                        <option value="{{ $profile }}" {{ old('profile', $user->profile ?? 'operador') === $profile ? 'selected' : '' }}>{{ ucfirst($profile) }}</option>
                    @endforeach
                </select>
                @error('profile')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="active" value="1" {{ old('active', $user->active ?? true) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Ativo</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">{{ $user ? 'Atualizar' : 'Salvar' }}</button>
            <a href="{{ route('users.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400">Cancelar</a>
        </div>
    </form>
</div>
@endsection
