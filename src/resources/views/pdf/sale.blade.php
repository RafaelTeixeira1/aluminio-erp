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
            font-size: 12px;
            color: #111827;
            background: #ffffff;
        }
        .preview-wrap {
            padding: 0;
        }
        .sheet {
            width: 794px;
            min-height: 1123px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px;
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
            padding-bottom: 14px;
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
            width: 220px;
            max-height: 78px;
            object-fit: contain;
            object-position: left center;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }
        .muted { color: #6b7280; }
        .doc-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        .meta-grid {
            margin-top: 14px;
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
            padding: 10px 12px;
            vertical-align: top;
        }
        .meta-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
            letter-spacing: 0.4px;
        }
        .section-title {
            margin: 18px 0 8px;
            font-size: 13px;
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
            padding: 7px;
        }
        .items-table th {
            background: #f9fafb;
            text-transform: uppercase;
            font-size: 10px;
            color: #4b5563;
            letter-spacing: 0.4px;
        }
        .right { text-align: right; }
        .totals {
            width: 300px;
            margin-left: auto;
            margin-top: 14px;
            border-collapse: collapse;
        }
        .totals td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
        }
        .totals tr.total td {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
        }
        .footer {
            margin-top: 26px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 11px;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pendente { background: #fef3c7; color: #92400e; }
        .status-confirmada { background: #d1fae5; color: #065f46; }
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
    @endphp
    <div class="preview-wrap" @if($isPreview) style="padding:24px 0;" @endif>
        @if($isPreview)
            <div class="toolbar" style="display:block;">
                <a href="{{ route('sales.printPdf', $sale) }}" target="_blank">Abrir PDF</a>
                <a href="javascript:window.print()">Imprimir</a>
            </div>
        @endif

        <div class="sheet" @if($isPreview) style="border:1px solid #d1d5db; box-shadow:0 8px 28px rgba(15, 23, 42, 0.12);" @endif>
            <div class="header">
                <div class="header-col">
                    @if (!empty($companyLogoDataUri))
                        <img src="{{ $companyLogoDataUri }}" alt="Logo da empresa" class="logo">
                    @else
                        <p class="company-name">SD Aluminios</p>
                    @endif
                    <p class="muted" style="margin:0;">Confirmação de Venda</p>
                </div>
                <div class="header-col right">
                    <p class="doc-title">VENDA</p>
                    <p style="margin: 6px 0 0;"><strong>#{{ $sale->id }}</strong></p>
                    @if($sale->quote_id)
                        <p class="muted" style="margin: 2px 0 0;">Orçamento: #{{ $sale->quote_id }}</p>
                    @endif
                    <p class="muted" style="margin: 2px 0 0;">Emissao: {{ optional($sale->created_at)->format('d/m/Y H:i') }}</p>
                    @if($sale->confirmed_at)
                        <p class="muted" style="margin: 2px 0 0;">Confirmada: {{ optional($sale->confirmed_at)->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>

            <div class="meta-grid">
                <div class="meta-row">
                    <div class="meta-cell">
                        <div class="meta-label">Cliente</div>
                        <strong>{{ $sale->client?->name ?? '-' }}</strong>
                    </div>
                    <div class="meta-cell">
                        <div class="meta-label">Status</div>
                        <span class="status-badge status-{{ $sale->status }}">{{ ucfirst($sale->status) }}</span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-cell">
                        <div class="meta-label">Telefone</div>
                        {{ $sale->client?->phone ?? '-' }}
                    </div>
                    <div class="meta-cell">
                        <div class="meta-label">E-mail</div>
                        {{ $sale->client?->email ?? '-' }}
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-cell">
                        <div class="meta-label">Responsavel</div>
                        {{ $sale->createdBy?->name ?? 'Sistema' }}
                    </div>
                    <div class="meta-cell">
                        <div class="meta-label">Data da Venda</div>
                        {{ $sale->created_at->format('d/m/Y') }}
                    </div>
                </div>
            </div>

            <h3 class="section-title">Itens da Venda</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Tipo</th>
                        <th class="right">Qtd</th>
                        <th class="right">Valor Unit.</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sale->items as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ ucfirst((string) $item->item_type) }}</td>
                            <td class="right">{{ number_format((float)$item->quantity, 3, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format((float)$item->unit_price, 2, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format((float)$item->line_total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Nenhum item nesta venda</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">R$ {{ number_format((float)$sale->subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Desconto</td>
                    <td class="right">R$ {{ number_format((float)$sale->discount, 2, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Total Final</td>
                    <td class="right">R$ {{ number_format((float)$sale->total, 2, ',', '.') }}</td>
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
                            <div>📞 {{ config('app.company_phone') }}</div>
                        @endif
                        @if(config('app.company_whatsapp'))
                            <div>💬 WhatsApp: {{ config('app.company_whatsapp') }}</div>
                        @endif
                        @if(config('app.company_contact_email'))
                            <div>📧 {{ config('app.company_contact_email') }}</div>
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
