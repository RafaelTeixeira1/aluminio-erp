@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Gerenciar Clientes')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Clientes</h1>
        <p class="text-gray-600 mt-1">Cadastre e acompanhe o histórico comercial dos clientes</p>
    </div>
    <a href="{{ route('clients.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        + Novo Cliente
    </a>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contato</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vendas</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Gasto</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($clients as $client)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $client->name }}</p>
                        @if($client->address)
                            <p class="text-xs text-gray-500 truncate max-w-xs">{{ $client->address }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-900">{{ $client->phone }}</p>
                        <p class="text-xs text-gray-500">{{ $client->email ?: 'Sem e-mail' }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $client->document ?: '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ $client->sales_count }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">R$ {{ number_format((float) ($client->total_spent ?? 0), 2, ',', '.') }}</td>
                    <td class="px-6 py-4 text-sm text-right space-x-2">
                        <a href="{{ route('clients.edit', $client) }}" class="text-blue-600 hover:text-blue-900">Editar</a>
                        <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline" onsubmit="return confirm('Deseja realmente remover este cliente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Nenhum cliente cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($clients->hasPages())
    <div class="mt-4">
        {{ $clients->links() }}
    </div>
@endif
@endsection
