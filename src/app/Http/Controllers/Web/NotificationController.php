<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Services\OperationalNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private readonly OperationalNotificationService $notificationService)
    {
    }

    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->notificationService->syncForUser($user);

        $notifications = UserNotification::query()
            ->where('user_id', $user->id)
            ->orderByRaw("CASE notification_key WHEN 'receivables.overdue' THEN 1 WHEN 'payables.overdue' THEN 2 WHEN 'stock.low' THEN 3 WHEN 'purchase.pending' THEN 4 WHEN 'quotes.open' THEN 5 ELSE 99 END")
            ->latest('updated_at')
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(UserNotification $notification): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->notificationService->markAsRead($notification, $user);

        return redirect()->route('notifications.index')->with('success', 'Notificacao marcada como lida.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->notificationService->markAllAsRead($user);

        return redirect()->route('notifications.index')->with('success', 'Todas as notificacoes foram marcadas como lidas.');
    }
}
