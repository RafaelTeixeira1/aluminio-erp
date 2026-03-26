<?php

namespace App\Services;

use App\Mail\QuoteEmail;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailService
{
    public function __construct(private readonly QuoteDocumentSettingsService $quoteDocumentSettingsService)
    {
    }

    /**
     * Enviar orçamento por email
     */
    public function sendQuoteEmail(Quote $quote, string $recipientEmail): void
    {
        $quote->loadMissing(['client', 'items', 'designSketches', 'pieceDesigns']);

        // Gerar PDF temporário
        $pdfFileName = 'quote-'.$quote->id.'.pdf';
        $pdfPath = 'temp/'.$pdfFileName;

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'previewMode' => false,
            'companyLogoDataUri' => $this->getCompanyLogoDataUri(true),
            'quoteSettings' => $this->quoteDocumentSettingsService->load(),
        ])->setPaper('a4');

        Storage::disk('local')->put($pdfPath, $pdf->output());

        // Enviar email
        Mail::to($recipientEmail)->send(new QuoteEmail($quote));

        // Limpar arquivo temporário após envio (opcional: deixar para limpeza em background job)
        // Storage::disk('local')->delete($pdfPath);
    }

    /**
     * Obter logo da empresa em formato Data URI
     */
    private function getCompanyLogoDataUri(bool $preferPdfSafe = false): string
    {
        $pdfSafeFirst = [
            ['path' => public_path('images/company-logo.jpg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.jpeg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.svg'), 'mime' => 'image/svg+xml'],
            ['path' => public_path('images/company-logo.png'), 'mime' => 'image/png'],
        ];
        $rasterFirst = [
            ['path' => public_path('images/company-logo.png'), 'mime' => 'image/png'],
            ['path' => public_path('images/company-logo.jpg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.jpeg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('images/company-logo.svg'), 'mime' => 'image/svg+xml'],
        ];

        $candidates = $preferPdfSafe ? $pdfSafeFirst : $rasterFirst;

        foreach ($candidates as $candidate) {
            if (!is_file($candidate['path'])) {
                continue;
            }

            $content = file_get_contents($candidate['path']);
            if ($content === false) {
                continue;
            }

            return 'data:'.$candidate['mime'].';base64,'.base64_encode($content);
        }

        return '';
    }
}
