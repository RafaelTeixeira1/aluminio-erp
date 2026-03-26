@extends('layouts.app')

@section('title', 'Meu Perfil')
@section('page-title', 'Meu Perfil')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">Dados da Conta</h1>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>

        <div class="border-t border-gray-200 pt-4">
            <h2 class="text-sm font-semibold text-gray-800 mb-2">Alterar senha (opcional)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <input type="password" name="current_password" placeholder="Senha atual" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <input type="password" name="new_password" placeholder="Nova senha" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <input type="password" name="new_password_confirmation" placeholder="Confirmar nova senha" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Salvar alterações</button>
        </div>
    </form>
</div>
@endsection
