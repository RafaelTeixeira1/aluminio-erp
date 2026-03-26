<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\Sale;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReceivableService
{
    public function __construct(
        private readonly CashFlowService $cashFlowService,
        private readonly AuditLogService $auditLogService,
    )
    {
    }

    public function createFromSale(Sale $sale, ?int $userId): Receivable
    {
        $existing = Receivable::query()
            ->where('sale_id', $sale->id)
            ->orderBy('installment_number')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $total = (float) $sale->total;

        $receivable = Receivable::query()->create([
            'sale_id' => $sale->id,
            'installment_number' => 1,
            'installment_count' => 1,
            'client_id' => $sale->client_id,
            'created_by_user_id' => $userId,
            'settled_by_user_id' => null,
            'status' => 'aberto',
            'amount_total' => $total,
            'amount_paid' => 0,
            'balance' => $total,
            'due_date' => now()->toDateString(),
            'settled_at' => null,
            'notes' => 'Gerado automaticamente na confirmacao da venda #'.$sale->id,
        ]);

        $this->auditLogService->record(
            action: 'receivable.created_from_sale',
            userId: $userId,
            entityType: Receivable::class,
            entityId: $receivable->id,
            payload: [
                'sale_id' => $sale->id,
                'amount_total' => (float) $receivable->amount_total,
            ],
        );

        return $receivable;
    }

    public function settle(Receivable $receivable, float $amount, ?int $userId, ?Carbon $paidAt = null, ?string $notes = null): Receivable
    {
        if (in_array($receivable->status, ['quitado', 'cancelado'], true)) {
            throw new DomainException('Titulo nao pode receber baixa nesse status.');
        }

        if ($amount <= 0) {
            throw new DomainException('Valor da baixa deve ser maior que zero.');
        }

        $currentBalance = (float) $receivable->balance;
        if ($amount > $currentBalance) {
            throw new DomainException('Valor da baixa maior que o saldo em aberto.');
        }

        $newPaid = (float) $receivable->amount_paid + $amount;
        $newBalance = max(0, (float) $receivable->amount_total - $newPaid);
        $fullySettled = $newBalance <= 0.00001;

        $effectivePaidAt = $paidAt ?? now();

        $receivable->update([
            'amount_paid' => $newPaid,
            'balance' => $newBalance,
            'status' => $fullySettled ? 'quitado' : 'parcial',
            'settled_by_user_id' => $userId,
            'settled_at' => $fullySettled ? $effectivePaidAt : null,
            'notes' => $notes !== null && trim($notes) !== '' ? $notes : $receivable->notes,
        ]);

        $this->cashFlowService->registerEntry(
            type: 'entrada',
            amount: $amount,
            description: 'Baixa conta a receber #'.$receivable->id,
            originType: 'receivable',
            originId: $receivable->id,
            userId: $userId,
            occurredAt: $effectivePaidAt,
            notes: $notes,
        );

        $this->auditLogService->record(
            action: 'receivable.settled',
            userId: $userId,
            entityType: Receivable::class,
            entityId: $receivable->id,
            payload: [
                'amount' => $amount,
                'amount_paid' => $newPaid,
                'balance' => $newBalance,
                'status' => $fullySettled ? 'quitado' : 'parcial',
            ],
        );

        return $receivable->fresh(['client', 'sale', 'settledBy']);
    }

    /**
     * @return Collection<int, Receivable>
     */
    public function splitIntoInstallments(
        Receivable $receivable,
        int $installments,
        Carbon $firstDueDate,
        int $intervalDays,
        ?int $userId,
    ): Collection {
        if ($installments < 2) {
            throw new DomainException('Informe ao menos 2 parcelas.');
        }

        if ($intervalDays < 1) {
            throw new DomainException('Intervalo de parcelas deve ser maior que zero.');
        }

        if ((float) $receivable->amount_paid > 0 || (float) $receivable->balance < (float) $receivable->amount_total) {
            throw new DomainException('Nao e possivel parcelar titulo com baixa parcial.');
        }

        if (!in_array($receivable->status, ['aberto'], true)) {
            throw new DomainException('Apenas titulos em aberto podem ser parcelados.');
        }

        if ((int) $receivable->installment_count > 1) {
            throw new DomainException('Titulo ja esta parcelado.');
        }

        $totalCents = (int) round(((float) $receivable->amount_total) * 100);
        $baseCents = intdiv($totalCents, $installments);
        $remainder = $totalCents % $installments;

        return DB::transaction(function () use ($receivable, $installments, $firstDueDate, $intervalDays, $userId, $baseCents, $remainder) {
            $payloads = [];
            for ($i = 1; $i <= $installments; $i++) {
                $installmentCents = $baseCents + ($i <= $remainder ? 1 : 0);
                $amount = $installmentCents / 100;

                $payloads[] = [
                    'sale_id' => $receivable->sale_id,
                    'installment_number' => $i,
                    'installment_count' => $installments,
                    'client_id' => $receivable->client_id,
                    'created_by_user_id' => $userId,
                    'settled_by_user_id' => null,
                    'status' => 'aberto',
                    'amount_total' => $amount,
                    'amount_paid' => 0,
                    'balance' => $amount,
                    'due_date' => $firstDueDate->copy()->addDays(($i - 1) * $intervalDays)->toDateString(),
                    'settled_at' => null,
                    'notes' => $receivable->notes,
                ];
            }

            $receivable->delete();

            Receivable::query()->insert($payloads);

            $this->auditLogService->record(
                action: 'receivable.split_installments',
                userId: $userId,
                entityType: Receivable::class,
                entityId: $receivable->id,
                payload: [
                    'installments' => $installments,
                    'interval_days' => $intervalDays,
                    'first_due_date' => $firstDueDate->toDateString(),
                ],
            );

            return Receivable::query()
                ->where('sale_id', $receivable->sale_id)
                ->orderBy('installment_number')
                ->get();
        });
    }
}