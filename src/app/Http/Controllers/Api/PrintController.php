<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PrintController extends Controller
{
    public function quotePdf(Quote $orcamento): Response
    {
        $orcamento->load(['client', 'items', 'designSketches', 'pieceDesigns']);

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $orcamento,
        ])->setPaper('a4');

        return $pdf->stream('orcamento-'.$orcamento->id.'.pdf');
    }

    public function salePdf(Sale $venda): Response
    {
        $venda->load(['client', 'items', 'quote']);

        $pdf = Pdf::loadView('pdf.sale', [
            'sale' => $venda,
        ])->setPaper('a4');

        return $pdf->stream('venda-'.$venda->id.'.pdf');
    }
}
