@extends('layouts.app')

@section('title', 'Configuracoes Comerciais')
@section('page-title', 'Configuracoes Comerciais do Orcamento')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Configuracoes Comerciais</h1>
        <p class="text-gray-600 mt-1">Defina textos padrao do PDF de orcamento sem editar arquivos do sistema.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('settings.commercial.update') }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Condicao de pagamento</label>
                <input name="quote_payment_terms" value="{{ old('quote_payment_terms', $settings['quote_payment_terms'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Prazo de entrega</label>
                <input name="quote_delivery_deadline" value="{{ old('quote_delivery_deadline', $settings['quote_delivery_deadline'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Garantia</label>
                <input name="quote_warranty" value="{{ old('quote_warranty', $settings['quote_warranty'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Frete e instalacao</label>
                <input name="quote_shipping_terms" value="{{ old('quote_shipping_terms', $settings['quote_shipping_terms'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Validade da proposta</label>
            <input name="quote_validity_terms" value="{{ old('quote_validity_terms', $settings['quote_validity_terms'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Observacoes legais</label>
            <textarea name="quote_legal_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('quote_legal_notes', $settings['quote_legal_notes'] ?? '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Texto de aceite</label>
            <textarea name="quote_acceptance_note" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('quote_acceptance_note', $settings['quote_acceptance_note'] ?? '') }}</textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">Salvar configuracoes</button>
        </div>
    </form>
</div>
@endsection
