@extends('layouts.app')

@section('title', 'Notificacoes')
@section('page-title', 'Notificacoes')

@section('content')
<div class="mx-auto max-w-4xl space-y-4">
    <div class="flex flex-col gap-3 rounded-lg bg-white p-4 shadow sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Notificacoes operacionais</h1>
            <p class="mt-1 text-sm text-gray-600">Alertas de estoque, compras e orcamentos.</p>
        </div>

        <form method="POST" action="{{ route('notifications.markAllRead') }}">
            @csrf
            <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900 transition">
                Marcar todas como lidas
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-300 bg-green-100 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg bg-white shadow">
        @forelse($notifications as $notification)
            @php
                $severity = (string) ($notification->meta['severity'] ?? 'info');
                $rowClass = match ($severity) {
                    'critical' => $notification->is_read ? 'bg-white border-l-4 border-l-red-300' : 'bg-red-50 border-l-4 border-l-red-500',
                    'warning' => $notification->is_read ? 'bg-white border-l-4 border-l-amber-300' : 'bg-amber-50 border-l-4 border-l-amber-500',
                    default => $notification->is_read ? 'bg-white border-l-4 border-l-blue-200' : 'bg-blue-50/40 border-l-4 border-l-blue-400',
                };
                $badgeClass = match ($severity) {
                    'critical' => 'bg-red-100 text-red-700',
                    'warning' => 'bg-amber-100 text-amber-700',
                    default => 'bg-blue-100 text-blue-700',
                };
                $badgeLabel = match ($severity) {
                    'critical' => 'Critica',
                    'warning' => 'Atencao',
                    default => 'Info',
                };
            @endphp
            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between {{ $rowClass }}">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900">{{ $notification->title }}</p>
                    <p class="mt-1 text-sm text-gray-700">{{ $notification->message }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $notification->updated_at?->format('d/m/Y H:i') }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    @if($notification->url)
                        <a href="{{ $notification->url }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 transition">Abrir</a>
                    @endif

                    @if(!$notification->is_read)
                        <form method="POST" action="{{ route('notifications.markRead', $notification) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 transition">Marcar como lida</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-sm text-gray-500">
                Nenhuma notificacao no momento.
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
