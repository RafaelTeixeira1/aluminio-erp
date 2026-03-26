<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'status' => ['nullable', 'in:aberto,aprovado,convertido,cancelado,expirado'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:boleto,pix,cartao,boleto_pix,pix_cartao,boleto_cartao,misto'],
            'notes' => ['nullable', 'string'],
            'item_quantification_notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.width_mm' => ['nullable', 'numeric', 'min:0'],
            'items.*.height_mm' => ['nullable', 'numeric', 'min:0'],
            'items.*.existing_image' => ['nullable', 'string', 'max:255'],
            'items.*.image' => ['nullable', 'file', 'image', 'max:2048'],
            'items.*.remove_image' => ['nullable', 'boolean'],
            'items.*.bnf' => ['nullable', 'string', 'max:80'],
            'items.*.bar_cut_size' => ['nullable', 'string', 'max:80'],
            'items.*.pieces_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.item_observation' => ['nullable', 'string', 'max:500'],
            'items.*.weight_per_meter_kg' => ['nullable', 'numeric', 'min:0'],
            'sketch_enabled' => ['nullable', 'boolean'],
            'sketch_id' => ['nullable', 'integer'],
            'sketch_title' => ['nullable', 'string', 'max:160'],
            'sketch_width_mm' => ['nullable', 'numeric', 'gt:0'],
            'sketch_height_mm' => ['nullable', 'numeric', 'gt:0'],
            'sketch_canvas_json' => ['nullable', 'string'],
            'sketch_preview_png' => ['nullable', 'string'],
            'sketch_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);
            if (!is_array($items)) {
                return;
            }

            $validRows = 0;

            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $quantityRaw = $item['quantity'] ?? null;
                $productRaw = $item['catalog_item_id'] ?? null;

                $quantity = is_numeric($quantityRaw) ? (float) $quantityRaw : 0;
                $hasQuantity = $quantity > 0;
                $hasProduct = is_numeric($productRaw) && (int) $productRaw > 0;

                $hasAnyFilledData = false;
                foreach ($item as $key => $value) {
                    if (in_array($key, ['item_name', 'item_type', 'unit_price', 'existing_image', 'weight_per_meter_kg'], true)) {
                        continue;
                    }

                    if (is_array($value)) {
                        if ($value !== []) {
                            $hasAnyFilledData = true;
                            break;
                        }
                        continue;
                    }

                    if (is_string($value) && trim($value) !== '') {
                        $hasAnyFilledData = true;
                        break;
                    }

                    if (is_numeric($value) && (float) $value > 0) {
                        $hasAnyFilledData = true;
                        break;
                    }

                    if (is_bool($value) && $value === true) {
                        $hasAnyFilledData = true;
                        break;
                    }
                }

                if (!$hasAnyFilledData) {
                    continue;
                }

                if (!$hasProduct) {
                    $validator->errors()->add("items.$index.catalog_item_id", 'Selecione um produto para este item.');
                }

                if (!$hasQuantity) {
                    $validator->errors()->add("items.$index.quantity", 'Informe uma quantidade maior que zero para este item.');
                }

                if ($hasProduct && $hasQuantity) {
                    $validRows += 1;
                }
            }

            if ($validRows === 0) {
                $validator->errors()->add('items', 'Adicione pelo menos 1 item com produto e quantidade maior que zero.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'O cliente selecionado é inválido.',
            'items.array' => 'Os itens do orçamento estão inválidos.',
            'items.*.catalog_item_id.exists' => 'Um dos produtos selecionados é inválido.',
            'items.*.quantity.numeric' => 'A quantidade precisa ser numérica.',
            'items.*.quantity.min' => 'A quantidade não pode ser negativa.',
        ];
    }
}
