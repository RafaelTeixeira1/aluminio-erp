<x-mail::message>
# Orçamento #{!! $quote->id !!}

Olá {{ $clientName }},

Segue em anexo o orçamento solicitado. 

## Detalhes do Orçamento
- **Cliente:** {{ $clientName }}
- **Data de Emissão:** {{ now()->format('d/m/Y') }}
- **Validade:** até {{ $quote->valid_until->format('d/m/Y') }}
- **Status:** @switch($quote->status)
    @case('aberto')
        <span class="text-blue-600">Aberto</span>
    @break
    @case('aprovado')
        <span class="text-green-600">Aprovado</span>
    @break
    @case('convertido')
        <span class="text-purple-600">Convertido</span>
    @break
    @case('expirado')
        <span class="text-red-600">Expirado</span>
    @break
    @endswitch

## Resumo Financeiro
- **Subtotal:** R$ {{ number_format($quote->subtotal, 2, ',', '.') }}
- **Desconto:** R$ {{ number_format($quote->discount, 2, ',', '.') }}
- **Total:** R$ {{ number_format($quote->total, 2, ',', '.') }}

## Itens
| Produto | Quantidade | Preço Unitário | Total |
|---------|-----------|-----------------|-------|
@forelse($quote->items as $item)
| {{ $item->item_name }} | {{ $item->quantity }} | R$ {{ number_format($item->unit_price, 2, ',', '.') }} | R$ {{ number_format($item->line_total, 2, ',', '.') }} |
@empty
| (nenhum item) | - | - | - |
@endforelse

---

Se tiver dúvidas ou desejar esclarecer qualquer ponto deste orçamento, entre em contato conosco:

📞 **Telefone:** {{ $phone }}
🔗 **WhatsApp:** {{ $whatsapp }}
📧 **Email:** {{ $contactEmail }}

Atenciosamente,

**SD Aluminios**

@slot('subcopy')
Este é um email automático. Não responda diretamente. Para informações, utilize os contatos acima.
@endslot
</x-mail::message>
