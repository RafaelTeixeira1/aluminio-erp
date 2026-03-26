<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceQuoteItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['nullable', 'in:produto,acessorio'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.width_mm' => ['nullable', 'numeric', 'gt:0'],
            'items.*.height_mm' => ['nullable', 'numeric', 'gt:0'],
            'items.*.metadata' => ['nullable', 'array'],
        ];
    }
}
