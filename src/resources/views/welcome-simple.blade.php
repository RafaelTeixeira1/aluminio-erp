@extends('layouts.app')

@section('title', 'Bem-vindo')
@section('page-title', 'Página em Desenvolvimento')

@section('content')
    <div class="flex flex-col items-center justify-center px-4 py-12">
        <div class="text-center max-w-md">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-6">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-3">Página em Desenvolvimento</h1>
            <p class="text-gray-600 mb-6">Esta página ainda não foi implementada, mas as funcionalidades estão disponíveis via API.</p>
            
            <div class="bg-blue-50 rounded-lg p-4 mb-6 text-left">
                <p class="text-sm font-semibold text-blue-900 mb-2">📡 Documentação da API</p>
                <p class="text-xs text-blue-800 mb-3">Acesse a API Rest em:</p>
                <code class="block bg-blue-100 p-2 rounded text-xs text-blue-900 font-mono mb-3">GET /api/...</code>
                <p class="text-xs text-blue-800">As telas web estão sendo criadas gradualmente. Enquanto isso, use a API REST diretamente!</p>
            </div>

            <a href="/" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Voltar
            </a>
        </div>
    </div>
@endsection
