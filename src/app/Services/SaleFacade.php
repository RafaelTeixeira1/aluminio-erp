<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Sale;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Padrao Facade: ponto unico de acesso as operacoes de venda que envolvem
 * varios modulos (comercial, estoque, financeiro e auditoria).
 *
 * Os controladores chamam apenas esta classe. Ela coordena os servicos do
 * subsistema na ordem correta e dentro de uma unica transacao: se qualquer
 * etapa falhar, todas as alteracoes sao desfeitas.
 */
class SaleFacade
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly SaleService $saleService,
        private readonly StockService $stockService,
        private readonly ReceivableService $receivableService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * Converte um orcamento em venda pendente.
     * A situacao do orcamento (State) decide se a conversao e permitida.
     */
    public function converterOrcamentoEmVenda(Quote $quote, ?int $userId): Sale
    {
        return DB::transaction(function () use ($quote, $userId): Sale {
            // Trava o registro para que duas solicitacoes simultaneas nao convertam o mesmo orcamento.
            $quote = Quote::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();

            $this->quoteService->assertCanConvert($quote);

            if (Sale::query()->where('quote_id', $quote->id)->exists()) {
                throw new DomainException('Este orcamento ja foi convertido em venda.');
            }

            $sale = $this->saleService->createPendingFromQuote($quote, $userId);

            $this->quoteService->markAsConverted($quote);

            $this->auditLogService->record(
                action: 'quote.converted_to_sale',
                userId: $userId,
                entityType: Sale::class,
                entityId: $sale->id,
                payload: [
                    'quote_id' => $quote->id,
                    'total' => (float) $sale->total,
                ],
            );

            return $sale->fresh(['items', 'client']);
        });
    }

    /**
     * Confirma a venda: baixa o estoque, gera as contas a receber e registra a auditoria.
     *
     * @param array<string, mixed> $financialOptions
     */
    public function confirmarVenda(Sale $sale, ?int $userId, array $financialOptions = []): Sale
    {
        $this->saleService->assertEditable($sale);

        return DB::transaction(function () use ($sale, $userId, $financialOptions): Sale {
            $sale->loadMissing('items.catalogItem');

            foreach ($sale->items as $item) {
                if ($item->catalog_item_id === null) {
                    continue;
                }

                $catalogItem = $item->catalogItem;
                if ($catalogItem === null) {
                    throw new DomainException('Item de venda sem produto vinculado.');
                }

                $this->stockService->outputForSale(
                    item: $catalogItem,
                    quantity: (float) $item->quantity,
                    userId: $userId,
                    saleId: (int) $sale->id,
                );
            }

            $sale->update([
                'status' => 'confirmada',
                'confirmed_at' => now(),
            ]);

            $receivable = $this->receivableService->createFromSale($sale, $userId);

            $installments = max(1, (int) ($financialOptions['installments'] ?? 1));
            if ($installments > 1) {
                $firstDueDate = !empty($financialOptions['first_due_date'])
                    ? Carbon::parse((string) $financialOptions['first_due_date'])
                    : now();
                $intervalDays = max(1, (int) ($financialOptions['interval_days'] ?? 30));

                $this->receivableService->splitIntoInstallments(
                    receivable: $receivable,
                    installments: $installments,
                    firstDueDate: $firstDueDate,
                    intervalDays: $intervalDays,
                    userId: $userId,
                );
            }

            $this->auditLogService->record(
                action: 'sale.confirmed',
                userId: $userId,
                entityType: Sale::class,
                entityId: $sale->id,
                payload: [
                    'total' => (float) $sale->total,
                    'items_count' => $sale->items->count(),
                ],
            );

            return $sale->fresh(['items', 'client', 'quote']);
        });
    }

    /**
     * Cancela a venda. Se ela ja estava confirmada, estorna o estoque e cancela
     * as contas a receber. Vendas com recebimento registrado nao podem ser canceladas.
     */
    public function cancelarVenda(Sale $sale, ?int $userId, ?string $motivo = null): Sale
    {
        return DB::transaction(function () use ($sale, $userId, $motivo): Sale {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status === 'cancelada') {
                throw new DomainException('Esta venda ja foi cancelada.');
            }

            $wasConfirmed = $sale->status === 'confirmada';

            if ($wasConfirmed) {
                $this->receivableService->cancelForSale($sale, $userId);

                $sale->load('items.catalogItem');
                foreach ($sale->items as $item) {
                    if ($item->catalogItem === null) {
                        continue;
                    }

                    $this->stockService->reversalForCancelledSale(
                        item: $item->catalogItem,
                        quantity: (float) $item->quantity,
                        userId: $userId,
                        saleId: (int) $sale->id,
                    );
                }
            }

            $sale->update([
                'status' => 'cancelada',
                'cancelled_at' => now(),
                'cancellation_reason' => $motivo !== null && trim($motivo) !== '' ? trim($motivo) : null,
            ]);

            $this->auditLogService->record(
                action: 'sale.cancelled',
                userId: $userId,
                entityType: Sale::class,
                entityId: $sale->id,
                payload: [
                    'was_confirmed' => $wasConfirmed,
                    'total' => (float) $sale->total,
                    'reason' => $motivo,
                ],
            );

            return $sale->fresh(['items', 'client', 'quote']);
        });
    }
}
