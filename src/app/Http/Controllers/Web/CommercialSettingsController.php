<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\QuoteDocumentSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommercialSettingsController extends Controller
{
    public function __construct(private readonly QuoteDocumentSettingsService $settingsService)
    {
    }

    public function edit(): View
    {
        return view('settings.commercial', [
            'settings' => $this->settingsService->load(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'quote_payment_terms' => 'nullable|string',
            'quote_delivery_deadline' => 'nullable|string',
            'quote_warranty' => 'nullable|string',
            'quote_shipping_terms' => 'nullable|string',
            'quote_validity_terms' => 'nullable|string',
            'quote_legal_notes' => 'nullable|string',
            'quote_acceptance_note' => 'nullable|string',
        ]);

        $this->settingsService->save($data, $request->user()?->id);

        return back()->with('success', 'Configuracoes comerciais atualizadas com sucesso!');
    }
}
