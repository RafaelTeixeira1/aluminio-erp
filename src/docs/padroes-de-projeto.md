# Padrões de projeto aplicados

Este documento resume onde cada padrão está no código e qual problema ele resolve.

## Factory Method: itens do orçamento

Cada tipo de produto é vendido de uma forma diferente. O campo `sale_unit` do produto (`catalog_items.sale_unit`) define qual criador monta o item:

| `sale_unit` | Criador | Item | Cálculo do total |
|---|---|---|---|
| `m2` | `GlassItemCreator` | `GlassQuoteItem` | peças × largura × altura (mm → m²) × preço do m² |
| `barra` | `ProfileItemCreator` | `ProfileQuoteItem` | barras de 6 m × preço da barra (só barras inteiras) |
| `unidade`, `pacote` | `AccessoryItemCreator` | `AccessoryQuoteItem` | quantidade × preço da unidade ou do pacote |

- Creator: `app/Services/QuoteItems/QuoteItemCreator.php` (`montarItem()` chama o método fábrica `criarItem()`, valida e calcula).
- Produtos: `app/Models/QuoteItem.php` e as subclasses em `app/Models/QuoteItems/`.
- Escolha do criador: `QuoteItemCreatorResolver`. Produtos sem `sale_unit` continuam como `unidade`, então o comportamento antigo não muda.

## State: situação do orçamento

O status do orçamento continua gravado em `quotes.status`, mas as regras de cada situação ficam em classes em `app/States/Quote/`:

| Status | Pode editar | Pode converter | Transições |
|---|---|---|---|
| `aberto` | sim | sim, dentro da validade | aprovado, cancelado, expirado |
| `aprovado` | sim | sim, dentro da validade | aberto, cancelado, expirado |
| `convertido`, `cancelado`, `expirado` | não | não | nenhuma |

- `Quote::state()` devolve o estado atual, `Quote::transitionTo()` aplica uma mudança de status e `Quote::changeState()` é chamado pelos estados.
- O status `convertido` só é definido pela conversão em venda, nunca diretamente pelo formulário ou pela API.
- Operações recusadas lançam `InvalidQuoteTransition` (uma `DomainException`), que os controladores devolvem como erro 422 ou mensagem na tela.

## Facade: operações de venda

`app/Services/SaleFacade.php` é o ponto único usado pelos controladores para:

- `converterOrcamentoEmVenda()`: valida o orçamento (State), cria a venda pendente, marca o orçamento como convertido e registra a auditoria;
- `confirmarVenda()`: baixa o estoque, gera as contas a receber (com parcelamento) e registra a auditoria;
- `cancelarVenda()`: se a venda estava confirmada, estorna o estoque e cancela as contas a receber; vendas com recebimento registrado não podem ser canceladas.

Cada operação roda em uma única transação. Os serviços do subsistema (`QuoteService`, `SaleService`, `StockService`, `ReceivableService`, `AuditLogService`) continuam podendo ser usados diretamente quando necessário.

Novas rotas: `POST /vendas/{sale}/cancelar` (web) e `POST /api/vendas/{venda}/cancelar` (API), ambas apenas para o perfil `admin`.

## Testes

`tests/Feature/DesignPatternsTest.php` cobre os três padrões.
