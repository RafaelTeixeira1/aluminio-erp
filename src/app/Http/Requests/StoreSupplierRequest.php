<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20', 'unique:suppliers,document,' . ($supplierId ?? 'NULL')],
            'email' => ['nullable', 'email', 'max:255', 'unique:suppliers,email,' . ($supplierId ?? 'NULL')],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do fornecedor é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'document.unique' => 'Este documento já está em uso.',
        ];
    }
}
