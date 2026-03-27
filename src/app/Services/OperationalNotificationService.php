<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationalNotificationService
{
    public function syncForUser(User $user): void
    {
        if (!Schema::hasTable('user_notifications')) {
            return;
        }

        $alerts = [];

        if (Schema::hasTable('quotes')) {
            $openQuotes = (int) DB::table('quotes')->where('status', 'aberto')->count();
            if ($openQuotes > 0) {
                $alerts['quotes.open'] = [
                    'title' => 'Orcamentos abertos',
                    'message' => $openQuotes.' orcamento(s) aguardando andamento.',
                    'url' => '/orcamentos',
                    'meta' => ['count' => $openQuotes, 'severity' => 'info', 'priority' => 30],
                ];
            }
        }

        if (Schema::hasTable('catalog_items')) {
            $lowStock = (int) DB::table('catalog_items')->whereColumn('stock', '<=', 'stock_minimum')->count();
            if ($lowStock > 0) {
                $alerts['stock.low'] = [
                    'title' => 'Estoque baixo',
                    'message' => $lowStock.' item(ns) com estoque abaixo do minimo.',
                    'url' => '/estoque',
                    'meta' => ['count' => $lowStock, 'severity' => 'warning', 'priority' => 20],
                ];
            }
        }

        if (Schema::hasTable('purchase_orders')) {
            $pendingPurchases = (int) DB::table('purchase_orders')->whereIn('status', ['aberto', 'parcial'])->count();
            if ($pendingPurchases > 0) {
                $alerts['purchase.pending'] = [
                    'title' => 'Compras pendentes',
                    'message' => $pendingPurchases.' pedido(s) de compra com recebimento pendente.',
                    'url' => '/compras',
                    'meta' => ['count' => $pendingPurchases, 'severity' => 'warning', 'priority' => 20],
                ];
            }
        }

        if ((string) ($user->profile ?? '') === 'admin') {
            if (Schema::hasTable('receivables')) {
                $overdueReceivables = (int) DB::table('receivables')
                    ->whereIn('status', ['aberto', 'parcial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count();

                if ($overdueReceivables > 0) {
                    $alerts['receivables.overdue'] = [
                        'title' => 'Recebimentos vencidos',
                        'message' => $overdueReceivables.' conta(s) a receber vencida(s).',
                        'url' => '/financeiro/receber?status=vencido',
                        'meta' => ['count' => $overdueReceivables, 'severity' => 'critical', 'priority' => 10],
                    ];
                }
            }

            if (Schema::hasTable('payables')) {
                $overduePayables = (int) DB::table('payables')
                    ->whereIn('status', ['aberto', 'parcial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count();

                if ($overduePayables > 0) {
                    $alerts['payables.overdue'] = [
                        'title' => 'Pagamentos vencidos',
                        'message' => $overduePayables.' conta(s) a pagar vencida(s).',
                        'url' => '/financeiro/pagar?status=vencido',
                        'meta' => ['count' => $overduePayables, 'severity' => 'critical', 'priority' => 10],
                    ];
                }
            }
        }

        $activeKeys = [];

        foreach ($alerts as $key => $alert) {
            $activeKeys[] = $key;

            $notification = UserNotification::query()->firstOrNew([
                'user_id' => $user->id,
                'notification_key' => $key,
            ]);

            $newCount = (int) ($alert['meta']['count'] ?? 0);
            $oldCount = (int) (($notification->meta['count'] ?? -1));
            $keepReadState = $notification->exists && (bool) $notification->is_read && $oldCount === $newCount;

            $notification->fill([
                'title' => $alert['title'],
                'message' => $alert['message'],
                'url' => $alert['url'],
                'meta' => $alert['meta'],
                'is_read' => $keepReadState,
                'read_at' => $keepReadState ? $notification->read_at : null,
            ]);

            $notification->save();
        }

        UserNotification::query()
            ->where('user_id', $user->id)
            ->when($activeKeys !== [], fn ($query) => $query->whereNotIn('notification_key', $activeKeys))
            ->delete();
    }

    public function unreadForUser(User $user, int $limit = 10): Collection
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return (int) UserNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(UserNotification $notification, User $user): void
    {
        if ((int) $notification->user_id !== (int) $user->id) {
            return;
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAllAsRead(User $user): void
    {
        UserNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
