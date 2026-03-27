<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Payable;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebNotificationsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_and_can_mark_notifications_as_read(): void
    {
        $user = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        CatalogItem::query()->create([
            'name' => 'Perfil Notificacao',
            'item_type' => 'produto',
            'price' => 100,
            'stock' => 1,
            'stock_minimum' => 5,
            'is_active' => true,
        ]);

        Quote::query()->create([
            'quote_number' => 'Q-TST-001',
            'status' => 'aberto',
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'valid_until' => now()->addDays(10),
            'created_by_user_id' => $user->id,
            'sale_id' => null,
        ]);

        Receivable::query()->create([
            'status' => 'aberto',
            'amount_total' => 150,
            'amount_paid' => 0,
            'balance' => 150,
            'due_date' => now()->subDays(2)->toDateString(),
            'created_by_user_id' => $user->id,
        ]);

        Payable::query()->create([
            'vendor_name' => 'Fornecedor Vencido',
            'description' => 'Conta vencida de teste',
            'category' => 'geral',
            'status' => 'aberto',
            'amount_total' => 120,
            'amount_paid' => 0,
            'balance' => 120,
            'due_date' => now()->subDays(3)->toDateString(),
            'created_by_user_id' => $user->id,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('notifications.index'));

        $indexResponse
            ->assertOk()
            ->assertSeeText('Notificacoes operacionais')
            ->assertSeeText('Orcamentos abertos')
            ->assertSeeText('Estoque baixo')
            ->assertSeeText('Recebimentos vencidos')
            ->assertSeeText('Pagamentos vencidos');

        $indexResponse->assertSeeTextInOrder([
            'Recebimentos vencidos',
            'Pagamentos vencidos',
            'Estoque baixo',
            'Orcamentos abertos',
        ]);

        $notification = $user->operationalNotifications()->where('notification_key', 'quotes.open')->firstOrFail();

        $this->actingAs($user)
            ->post(route('notifications.markRead', $notification))
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('user_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);

        $this->actingAs($user)
            ->post(route('notifications.markAllRead'))
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $user->id,
            'is_read' => false,
        ]);
    }
}
