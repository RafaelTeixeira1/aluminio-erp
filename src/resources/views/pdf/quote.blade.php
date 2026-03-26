<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page { margin: 24px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            background: #ffffff;
        }
        .preview-wrap {
            padding: 0;
        }
        .sheet {
            width: auto;
            min-height: auto;
            margin: 0;
            background: #ffffff;
            padding: 0;
            border: none;
            box-shadow: none;
        }
        .toolbar {
            width: 794px;
            margin: 0 auto 12px;
            display: none;
            text-align: right;
        }
        .toolbar a {
            display: inline-block;
            text-decoration: none;
            background: #0f172a;
            color: #fff;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 12px;
            margin-left: 8px;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .header-col {
            display: table-cell;
            vertical-align: top;
        }
        .header-col.right {
            text-align: right;
            width: 260px;
        }
        .logo {
            width: 200px;
            max-height: 64px;
            object-fit: contain;
            object-position: left center;
            margin-bottom: 4px;
        }
        .company-name {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }
        .muted { color: #6b7280; }
        .quote-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
        .meta-grid {
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }
        .meta-row {
            display: table;
            width: 100%;
        }
        .meta-row + .meta-row { border-top: 1px solid #e5e7eb; }
        .meta-cell {
            display: table-cell;
            width: 50%;
            padding: 8px 10px;
            vertical-align: top;
        }
        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 3px;
            letter-spacing: 0.4px;
        }
        .section-title {
            margin: 10px 0 5px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #374151;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
        }
        .items-table th {
            background: #f9fafb;
            text-transform: uppercase;
            font-size: 9px;
            color: #4b5563;
            letter-spacing: 0.4px;
        }
        .items-table.compact th,
        .items-table.compact td {
            padding: 3px 4px;
            font-size: 9px;
        }
        .items-table.compact th {
            font-size: 8px;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .items-table.compact td {
            vertical-align: top;
        }
        .small-note {
            display: block;
            margin-top: 2px;
            font-size: 8px;
            color: #6b7280;
            line-height: 1.2;
        }
        .right { text-align: right; }
        .totals {
            width: 300px;
            margin-left: auto;
            margin-top: 8px;
            border-collapse: collapse;
        }
        .totals td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }
        .totals tr.total td {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
        }
        .totals tr.discount td {
            background: #fff7ed;
            color: #9a3412;
            font-weight: 600;
        }
        .footer {
            margin-top: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            font-size: 10px;
            color: #6b7280;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { box-shadow: none; border: none; margin: 0; width: auto; min-height: auto; }
        }
    </style>
</head>
<body @if(($previewMode ?? false) === true) style="background:#e5e7eb;" @endif>
    @php
        $isPreview = ($previewMode ?? false) === true;
        $settings = is_array($quoteSettings ?? null) ? $quoteSettings : [];
        $commercialTerms = [
            'Condicao de pagamento' => (string) ($settings['quote_payment_terms'] ?? config('app.quote_payment_terms', '')),
            'Meio de pagamento' => (string) match ((string) ($quote->payment_method ?? '')) {
                'boleto' => 'Boleto',
                'pix' => 'Pix',
                'cartao' => 'Cartao',
                'boleto_pix' => 'Boleto + Pix',
                'pix_cartao' => 'Pix + Cartao',
                'boleto_cartao' => 'Boleto + Cartao',
                'misto' => 'Misto',
                default => '-',
            },
            'Prazo de entrega' => (string) ($settings['quote_delivery_deadline'] ?? config('app.quote_delivery_deadline', '')),
            'Garantia' => (string) ($settings['quote_warranty'] ?? config('app.quote_warranty', '')),
            'Frete e instalacao' => (string) ($settings['quote_shipping_terms'] ?? config('app.quote_shipping_terms', '')),
            'Validade da proposta' => (string) ($settings['quote_validity_terms'] ?? config('app.quote_validity_terms', '')),
        ];

        $resolveImageDataUri = static function (mixed $raw): ?string {
            if (!is_string($raw) || trim($raw) === '') {
                return null;
            }

            $value = trim($raw);

            if (str_starts_with($value, 'data:image/')) {
                return $value;
            }

            if (!preg_match('/\s/', $value) && preg_match('/^[A-Za-z0-9+\/=]+$/', $value) && strlen($value) > 120) {
                return 'data:image/png;base64,'.$value;
            }

            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                $urlPath = parse_url($value, PHP_URL_PATH);
                if (is_string($urlPath) && $urlPath !== '') {
                    $value = ltrim($urlPath, '/');
                }
            }

            $publicPath = public_path(ltrim($value, '/'));
            if (is_file($publicPath)) {
                $ext = strtolower((string) pathinfo($publicPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };
                $content = file_get_contents($publicPath);
                return $content === false ? null : 'data:'.$mime.';base64,'.base64_encode($content);
            }

            return null;
        };
    @endphp
    <div class="preview-wrap" @if($isPreview) style="padding:24px 0;" @endif>
        @if($isPreview)
            <div class="toolbar" style="display:block;">
                <a href="{{ route('quotes.printPdf', $quote) }}" target="_blank">Abrir PDF</a>
                <a href="javascript:window.print()">Imprimir</a>
            </div>
        @endif

        <div class="sheet" @if($isPreview) style="width:794px; min-height:1123px; margin:0 auto; padding:24px; border:1px solid #d1d5db; box-shadow:0 8px 28px rgba(15, 23, 42, 0.12);" @endif>
            <div class="header">
                <div class="header-col">
                    @if (!empty($companyLogoDataUri))
                        <img src="{{ $companyLogoDataUri }}" alt="Logo da empresa" class="logo">
                    @else
                        <p class="company-name">SD Aluminios</p>
                    @endif
                    <p class="muted" style="margin:0;">Proposta comercial personalizada</p>
                </div>
                <div class="header-col right">
                    <p class="quote-title">ORCAMENTO</p>
                    <p style="margin: 6px 0 0;"><strong>#{{ $quote->id }}</strong></p>
                    <p class="muted" style="margin: 2px 0 0;">Emissao: {{ optional($quote->created_at)->format('d/m/Y H:i') }}</p>
                    <p class="muted" style="margin: 2px 0 0;">Validade: {{ $quote->valid_until?->format('d/m/Y') ?? '-' }}</p>
                </div>
            </div>

            <div class="meta-grid">
                <div class="meta-row">
                    <div class="meta-cell">
                        <div class="meta-label">Cliente</div>
                        @if($quote->client)
                            <strong>{{ $quote->client->name }}</strong>
                        @else
                            <span style="display:inline-block; padding:2px 7px; border-radius:999px; font-size:9px; font-weight:700; background:#ffedd5; color:#9a3412;">Orçamento rápido</span>
                        @endif
                    </div>
                    <div class="meta-cell">
                        <div class="meta-label">Telefone</div>
                        {{ $quote->client?->phone ?? '-' }}
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-cell">
                        <div class="meta-label">E-mail</div>
                        {{ $quote->client?->email ?? '-' }}
                    </div>
                    <div class="meta-cell">
                        <div class="meta-label">Vendedor</div>
                        {{ $quote->createdBy?->name ?? 'Sistema' }}
                    </div>
                </div>
            </div>

            <h3 class="section-title">Itens do Orcamento + Quantificacao Tecnica</h3>
            <table class="items-table compact">
                <colgroup>
                    <col style="width: 8%;">
                    <col style="width: 16%;">
                    <col style="width: 6%;">
                    <col style="width: 7%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 9%;">
                    <col style="width: 9%;">
                    <col style="width: 10%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Img</th>
                        <th>Item</th>
                        <th>Tp</th>
                        <th class="right">Qtd</th>
                        <th>BNF</th>
                        <th>Ct</th>
                        <th class="right">Q.Pcs</th>
                        <th class="right">KG/M</th>
                        <th class="right">Ps</th>
                        <th class="right">Ps Tot</th>
                        <th class="right">P.Unit</th>
                        <th class="right">Tot</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $item)
                        @php
                            $meta = is_array($item->metadata ?? null) ? $item->metadata : [];
                            $itemObservation = trim((string) ($meta['item_observation'] ?? ''));
                            $itemImageDataUri = $resolveImageDataUri($meta['image'] ?? null);
                            $itemWeightPerMeter = null;
                            if (is_numeric($meta['weight_per_meter_kg'] ?? null)) {
                                $itemWeightPerMeter = (float) $meta['weight_per_meter_kg'];
                            } elseif (preg_match('/kg\/m\s*(\d+[\.,]\d{2,3})/iu', (string) $item->item_name, $weightMatch) === 1) {
                                $normalizedWeight = str_replace(',', '.', $weightMatch[1]);
                                if (is_numeric($normalizedWeight)) {
                                    $itemWeightPerMeter = (float) $normalizedWeight;
                                }
                            }
                        @endphp
                        <tr>
                            <td>
                                @if($itemImageDataUri)
                                    <img src="{{ $itemImageDataUri }}" alt="Imagem do item" style="width:38px; height:38px; object-fit:cover; border:1px solid #e5e7eb; border-radius:3px;">
                                @else
                                    <span class="muted" style="font-size:8px;">-</span>
                                @endif
                            </td>
                            <td>
                                {{ $item->item_name }}
                                @if(is_numeric($meta['weight_per_meter_kg'] ?? null))
                                    <span class="small-note">kg/m: {{ number_format((float) $meta['weight_per_meter_kg'], 3, ',', '.') }}</span>
                                @endif
                                @if($itemObservation !== '')
                                    <span class="small-note">Obs: {{ $itemObservation }}</span>
                                @endif
                            </td>
                            <td>{{ ucfirst((string) $item->item_type) }}</td>
                            <td class="right">{{ number_format((float)$item->quantity, 3, ',', '.') }}</td>
                            <td>{{ trim((string) ($meta['bnf'] ?? '')) !== '' ? $meta['bnf'] : '-' }}</td>
                            <td>{{ trim((string) ($meta['bar_cut_size'] ?? '')) !== '' ? $meta['bar_cut_size'] : '-' }}</td>
                            <td class="right">{{ is_numeric($meta['pieces_quantity'] ?? null) ? number_format((float) $meta['pieces_quantity'], 3, ',', '.') : '-' }}</td>
                            <td class="right">{{ $itemWeightPerMeter !== null ? number_format((float) $itemWeightPerMeter, 3, ',', '.') : '-' }}</td>
                            <td class="right">{{ is_numeric($meta['weight'] ?? null) ? number_format((float) $meta['weight'], 3, ',', '.') : '-' }}</td>
                            <td class="right">{{ is_numeric($meta['total_weight'] ?? null) ? number_format((float) $meta['total_weight'], 3, ',', '.') : '-' }}</td>
                            <td class="right">R$ {{ number_format((float)$item->unit_price, 2, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format((float)$item->line_total, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if(!empty($quote->item_quantification_notes))
                <h3 class="section-title">Observacao da Quantificacao</h3>
                <p style="margin:0; line-height:1.3; font-size:10px;">{{ $quote->item_quantification_notes }}</p>
            @endif

            @if($quote->pieceDesigns->isNotEmpty())
                <h3 class="section-title">Detalhamento das Pecas</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="right">Largura (mm)</th>
                            <th class="right">Altura (mm)</th>
                            <th class="right">Quantidade</th>
                            <th>Referencia visual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quote->pieceDesigns as $piece)
                            @php
                                $pieceData = is_array($piece->data_json) ? $piece->data_json : [];
                                $possibleImageKeys = ['image', 'image_data_uri', 'image_base64', 'photo', 'preview', 'thumbnail', 'imagem'];
                                $pieceImageDataUri = null;
                                foreach ($possibleImageKeys as $imageKey) {
                                    if (array_key_exists($imageKey, $pieceData)) {
                                        $pieceImageDataUri = $resolveImageDataUri($pieceData[$imageKey]);
                                        if ($pieceImageDataUri !== null) {
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="right">{{ number_format((float)$piece->width_mm, 2, ',', '.') }}</td>
                                <td class="right">{{ number_format((float)$piece->height_mm, 2, ',', '.') }}</td>
                                <td class="right">{{ number_format((float)$piece->quantity, 3, ',', '.') }}</td>
                                <td>
                                    @if($pieceImageDataUri)
                                        <img src="{{ $pieceImageDataUri }}" alt="Referencia da peca" style="width: 140px; max-height: 90px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 4px;">
                                    @else
                                        <span class="muted">Sem imagem</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if($quote->designSketches->isNotEmpty())
                <h3 class="section-title">Desenhos Integrados do Orcamento</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Titulo</th>
                            <th class="right">Largura (mm)</th>
                            <th class="right">Altura (mm)</th>
                            <th>Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quote->designSketches as $sketch)
                            @php
                                $sketchPreviewDataUri = $resolveImageDataUri($sketch->preview_png);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $sketch->title }}</strong>
                                    @if(!empty($sketch->notes))
                                        <span class="small-note">Obs: {{ $sketch->notes }}</span>
                                    @endif
                                </td>
                                <td class="right">{{ $sketch->width_mm !== null ? number_format((float) $sketch->width_mm, 2, ',', '.') : '-' }}</td>
                                <td class="right">{{ $sketch->height_mm !== null ? number_format((float) $sketch->height_mm, 2, ',', '.') : '-' }}</td>
                                <td>
                                    @if($sketchPreviewDataUri)
                                        <img src="{{ $sketchPreviewDataUri }}" alt="Preview do desenho" style="width: 180px; max-height: 120px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 4px;">
                                    @else
                                        <span class="muted">Sem preview</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(collect($commercialTerms)->filter(fn ($value) => trim($value) !== '')->isNotEmpty())
                <h3 class="section-title">Condicoes Comerciais</h3>
                <table class="items-table">
                    <tbody>
                        @foreach($commercialTerms as $label => $value)
                            @if(trim($value) !== '')
                                <tr>
                                    <td style="width: 220px; background: #f9fafb;"><strong>{{ $label }}</strong></td>
                                    <td>{{ $value }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(trim((string) ($settings['quote_legal_notes'] ?? config('app.quote_legal_notes', ''))) !== '')
                <h3 class="section-title">Observacoes Legais</h3>
                <p style="margin:0; line-height:1.3; font-size:10px; color:#4b5563;">
                    {{ $settings['quote_legal_notes'] ?? config('app.quote_legal_notes') }}
                </p>
            @endif

            @if(!empty($quote->notes))
                <h3 class="section-title">Observacoes</h3>
                <p style="margin:0; line-height:1.35; font-size:10px;">{{ $quote->notes }}</p>
            @endif

            <table class="totals">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">R$ {{ number_format((float)$quote->subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr class="discount">
                    <td>Desconto</td>
                    <td class="right">R$ {{ number_format((float)$quote->discount, 2, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Total Final</td>
                    <td class="right">R$ {{ number_format((float)$quote->total, 2, ',', '.') }}</td>
                </tr>
            </table>

            <h3 class="section-title">Aceite e Assinaturas</h3>
            @if(trim((string) ($settings['quote_acceptance_note'] ?? config('app.quote_acceptance_note', ''))) !== '')
                <p style="margin:0 0 6px; line-height:1.25; font-size:9px; color:#4b5563;">
                    {{ $settings['quote_acceptance_note'] ?? config('app.quote_acceptance_note') }}
                </p>
            @endif
            <table style="width:100%; border-collapse: collapse; margin-top: 2px;">
                <tr>
                    <td style="width:48%; vertical-align: top; padding-right: 2%;">
                        <div style="height:24px;"></div>
                        <div style="border-top:1px solid #374151; padding-top:4px; text-align:center; font-size:10px;">Assinatura do Cliente</div>
                        <div style="text-align:center; font-size:9px; color:#6b7280; margin-top:1px;">Nome / CPF ou CNPJ / Data</div>
                    </td>
                    <td style="width:48%; vertical-align: top; padding-left: 2%;">
                        <div style="height:24px;"></div>
                        <div style="border-top:1px solid #374151; padding-top:4px; text-align:center; font-size:10px;">Assinatura do Responsavel</div>
                        <div style="text-align:center; font-size:9px; color:#6b7280; margin-top:1px;">{{ $quote->createdBy?->name ?? 'Equipe Comercial' }}</div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                <div style="display: table; width: 100%; border-top: 2px solid #e5e7eb; padding-top: 10px;">
                    <div style="display: table-cell; width: 50%; vertical-align: top;">
                        <div style="font-weight: 600; margin-bottom: 3px;">{{ config('app.company_name', 'SD Aluminios') }}</div>
                        @if(config('app.company_cnpj'))
                            <div>CNPJ: {{ config('app.company_cnpj') }}</div>
                        @endif
                        @if(config('app.company_address'))
                            <div>{{ config('app.company_address') }}</div>
                        @endif
                    </div>
                    <div style="display: table-cell; width: 50%; vertical-align: top; text-align: right;">
                        @if(config('app.company_phone'))
                            <div>Tel: {{ config('app.company_phone') }}</div>
                        @endif
                        @if(config('app.company_whatsapp'))
                            <div>WhatsApp: {{ config('app.company_whatsapp') }}</div>
                        @endif
                        @if(config('app.company_contact_email'))
                            <div>Email: {{ config('app.company_contact_email') }}</div>
                        @endif
                    </div>
                </div>
                <div style="text-align: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af;">
                    Documento gerado em {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
