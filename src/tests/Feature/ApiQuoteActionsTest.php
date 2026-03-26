<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiQuoteActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_blocks_duplicate_quote_conversion(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $headers = $this->authHeader($vendedor);
        $quote = $this->createQuote($vendedor);

        $first = $this->postJson('/api/orcamentos/'.$quote->id.'/converter', [], $headers);
        $first->assertStatus(201);

        $second = $this->postJson('/api/orcamentos/'.$quote->id.'/converter', [], $headers);
        $second
            ->assertStatus(422)
            ->assertJsonPath('message', 'Este orcamento ja foi convertido em venda.');

        $this->assertSame(1, Sale::query()->where('quote_id', $quote->id)->count());
    }

    /**
     * @return array{Authorization: string}
     */
    private function authHeader(User $user): array
    {
        $issued = ApiToken::issueForUser($user, 'tests');

        return [
            'Authorization' => 'Bearer '.$issued['plainTextToken'],
        ];
    }

    private function createQuote(User $createdBy): Quote
    {
        $client = Client::query()->create([
            'name' => 'Cliente API Quote',
            'phone' => '11988887777',
            'email' => 'api.quote@example.com',
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'created_by_user_id' => $createdBy->id,
            'status' => 'aberto',
            'subtotal' => 300,
            'discount' => 0,
            'total' => 300,
            'valid_until' => now()->addDays(10),
            'notes' => 'Quote API',
        ]);

        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'item_name' => 'Perfil API 30x30',
            'item_type' => 'perfil',
            'quantity' => 3,
            'unit_price' => 100,
            'line_total' => 300,
        ]);

        return $quote;
    }
}
