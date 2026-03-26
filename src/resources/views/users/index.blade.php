@extends('layouts.app')

@section('title', 'Usuários')
@section('page-title', 'Administração de Usuários')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Usuários</h1>
                <p class="text-gray-600 mt-1">Gerencie acessos, perfis e permissões do sistema</p>
            </div>
            <a href="{{ route('users.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Novo Usuário
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                <p class="text-sm text-gray-600">Total de Usuários</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $summary['total'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                <p class="text-sm text-gray-600">Ativos</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ $summary['active'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                <p class="text-sm text-gray-600">Sem Acesso Recente</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $summary['stale'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                <p class="text-sm text-gray-600">Inativos</p>
                <p class="text-3xl font-bold text-red-600 mt-2">{{ $summary['inactive'] }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="bg-white rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar usuário</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome ou e-mail..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Perfil</label>
                    <select name="profile" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="admin" {{ ($filters['profile'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="vendedor" {{ ($filters['profile'] ?? '') === 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                        <option value="estoquista" {{ ($filters['profile'] ?? '') === 'estoquista' ? 'selected' : '' }}>Estoquista</option>
                        <option value="operador" {{ ($filters['profile'] ?? '') === 'operador' ? 'selected' : '' }}>Operador</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Ativo</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex justify-end gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Aplicar</button>
                    <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Limpar</a>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfil</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Acesso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($users as $user)
                        @php
                            $initials = collect(explode(' ', (string) $user->name))
                                ->filter()
                                ->map(fn ($part) => mb_substr($part, 0, 1))
                                ->take(2)
                                ->implode('');
                            $profile = (string) ($user->profile ?? 'usuario');
                            $profileColors = [
                                'admin' => 'bg-purple-100 text-purple-800',
                                'vendedor' => 'bg-blue-100 text-blue-800',
                                'estoquista' => 'bg-yellow-100 text-yellow-800',
                                'operador' => 'bg-gray-100 text-gray-800',
                            ];
                            $profileColor = $profileColors[$profile] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold">{{ strtoupper($initials ?: 'U') }}</div>
                                    <div class="ml-4">
                                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $profileColor }}">{{ ucfirst($profile) }}</span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ optional($user->updated_at)->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($user->active)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:text-blue-900">Editar</a>
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Deseja remover este usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum usuario encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div>{{ $users->links() }}</div>
        @endif
    </div>
@endsection
