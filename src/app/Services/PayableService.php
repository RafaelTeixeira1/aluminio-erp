<?php

namespace App\Services;

use App\Models\Payable;
use Carbon\Carbon;
use DomainException;

class PayableService
{
    public function __construct(
        private readonly CashFlowService $cashFlowService,
        private readonly AuditLogService $auditLogService,
    )
    {
    }

    public function create(array $data, ?int $userId): Payable
    {
        $total = (float) ($data['amount_total'] ?? 0);
        if ($total <= 0) {
            throw new DomainException('Valor total deve ser maior que zero.');
        }

        $payable = Payable::query()->create([
            'vendor_name' => (string) ($data['vendor_name'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'category' => (string) ($data['category'] ?? 'geral'),
            'document_number' => $data['document_number'] ?? null,
            'created_by_user_id' => $userId,
            'settled_by_user_id' => null,
            'status' => 'aberto',
            'amount_total' => $total,
            'amount_paid' => 0,
            'balance' => $total,
            'due_date' => $data['due_date'] ?? null,
            'paid_at' => null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->auditLogService->record(
            action: 'payable.created',
            userId: $userId,
            entityType: Payable::class,
            entityId: $payable->id,
            payload: [
                'amount_total' => (float) $payable->amount_total,
                'due_date' => $payable->due_date?->toDateString(),
                'category' => $payable->category,
            ],
        );

        return $payable;
    }

    public function settle(Payable $payable, float $amount, ?int $userId, ?Carbon $paidAt = null, ?string $notes = null): Payable
    {
        if (in_array($payable->status, ['quitado', 'cancelado'], true)) {
            throw new DomainException('Conta nao pode receber baixa nesse status.');
        }

        if ($amount <= 0) {
            throw new DomainException('Valor da baixa deve ser maior que zero.');
        }

        $currentBalance = (float) $payable->balance;
        if ($amount > $currentBalance) {
            throw new DomainException('Valor da baixa maior que o saldo em aberto.');
        }

        $newPaid = (float) $payable->amount_paid + $amount;
        $newBalance = max(0, (float) $payable->amount_total - $newPaid);
        $fullySettled = $newBalance <= 0.00001;
        $effectivePaidAt = $paidAt ?? now();

        $payable->update([
            'amount_paid' => $newPaid,
            'balance' => $newBalance,
            'status' => $fullySettled ? 'quitado' : 'parcial',
            'settled_by_user_id' => $userId,
            'paid_at' => $effectivePaidAt,
            'notes' => $notes !== null && trim($notes) !== '' ? $notes : $payable->notes,
        ]);

        $this->cashFlowService->registerEntry(
            type: 'saida',
            amount: $amount,
            description: 'Baixa conta a pagar #'.$payable->id.' - '.$payable->vendor_name,
            originType: 'payable',
            originId: $payable->id,
            userId: $userId,
            occurredAt: $effectivePaidAt,
            notes: $notes,
        );

        $this->auditLogService->record(
            action: 'payable.settled',
            userId: $userId,
            entityType: Payable::class,
            entityId: $payable->id,
            payload: [
                'amount' => $amount,
                'amount_paid' => $newPaid,
                'balance' => $newBalance,
                'status' => $fullySettled ? 'quitado' : 'parcial',
            ],
        );

        return $payable->fresh(['createdBy', 'settledBy']);
    }

    public function cancel(Payable $payable, ?string $notes = null): Payable
    {
        if ($payable->status === 'quitado') {
            throw new DomainException('Conta quitada nao pode ser cancelada.');
        }

        $payable->update([
            'status' => 'cancelado',
            'notes' => $notes !== null && trim($notes) !== '' ? $notes : $payable->notes,
        ]);

        $this->auditLogService->record(
            action: 'payable.canceled',
            userId: $payable->settled_by_user_id,
            entityType: Payable::class,
            entityId: $payable->id,
            payload: ['status' => 'cancelado'],
        );

        return $payable->fresh(['createdBy', 'settledBy']);
    }
}
