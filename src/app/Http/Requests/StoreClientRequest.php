<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'document' => ['nullable', 'string', 'max:20', 'unique:clients,document,' . ($clientId ?? 'NULL')],
            'email' => ['nullable', 'email', 'max:255', 'unique:clients,email,' . ($clientId ?? 'NULL')],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do cliente é obrigatório.',
            'phone.required' => 'O telefone é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'document.unique' => 'Este documento já está em uso.',
        ];
    }
}