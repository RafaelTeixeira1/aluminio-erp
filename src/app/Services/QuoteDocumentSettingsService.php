<?php

namespace App\Services;

use App\Models\AppSetting;

class QuoteDocumentSettingsService
{
    /**
     * @return array<string, string>
     */
    public function load(): array
    {
        $defaults = $this->defaults();
        $saved = AppSetting::valuesByKeys(array_keys($defaults));

        $resolved = [];
        foreach ($defaults as $key => $default) {
            $resolved[$key] = array_key_exists($key, $saved) && trim($saved[$key]) !== ''
                ? $saved[$key]
                : $default;
        }

        return $resolved;
    }

    /**
     * @param array<string, string> $values
     */
    public function save(array $values, ?int $userId): void
    {
        $defaults = $this->defaults();

        foreach ($defaults as $key => $default) {
            $value = (string) ($values[$key] ?? '');

            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'updated_by_user_id' => $userId,
                ],
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'quote_payment_terms' => (string) config('app.quote_payment_terms', ''),
            'quote_delivery_deadline' => (string) config('app.quote_delivery_deadline', ''),
            'quote_warranty' => (string) config('app.quote_warranty', ''),
            'quote_shipping_terms' => (string) config('app.quote_shipping_terms', ''),
            'quote_validity_terms' => (string) config('app.quote_validity_terms', ''),
            'quote_legal_notes' => (string) config('app.quote_legal_notes', ''),
            'quote_acceptance_note' => (string) config('app.quote_acceptance_note', ''),
        ];
    }
}
