<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id ?? 'NULL';

        return [
            'name' => ['required', 'string', 'max:255', "unique:catalog_items,name,$id"],
            'category_id' => ['required', 'exists:categories,id'],
            'item_type' => ['required', 'in:produto,acessorio'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'stock' => ['required', 'numeric', 'min:0'],
            'stock_minimum' => ['required', 'numeric', 'min:0'],
            'weight_per_meter_kg' => ['nullable', 'numeric', 'min:0'],
            'material' => ['nullable', 'string', 'max:120'],
            'finish' => ['nullable', 'string', 'max:120'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0'],
            'standard_width_mm' => ['nullable', 'numeric', 'min:0'],
            'standard_height_mm' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:120'],
            'product_line' => ['nullable', 'string', 'max:120'],
            'technical_notes' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_kind' => ['nullable', 'in:perfil,roldana,acessorio,outro'],
            'gallery_images' => ['nullable', 'array', 'max:12'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'primary_image_id' => ['nullable', 'integer'],
            'remove_gallery_images' => ['nullable', 'array'],
            'remove_gallery_images.*' => ['integer'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.unique' => 'Este produto já existe.',
            'category_id.required' => 'Selecione uma categoria.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um número.',
        ];
    }
}
