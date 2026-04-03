<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['required', 'exists:catalog_items,id', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0.01'],
            'delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Fornecedor é obrigatório.',
            'supplier_id.exists' => 'Fornecedor selecionado não existe.',
            'items.required' => 'Adicione pelo menos um item ao pedido.',
            'items.*.catalog_item_id.required' => 'Selecione um produto.',
            'items.*.quantity.required' => 'Quantidade é obrigatória.',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero.',
            'items.*.unit_cost.required' => 'Preço unitário é obrigatório.',
            'items.*.unit_cost.min' => 'Preço unitário deve ser maior que zero.',
        ];
    }
}
