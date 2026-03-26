<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_disable_category(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $headers = $this->authHeader($admin);

        $createResponse = $this->postJson('/api/categorias', [
            'name' => 'Vidros',
            'active' => true,
        ], $headers);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('name', 'Vidros')
            ->assertJsonPath('active', true);

        $id = (int) $createResponse->json('id');

        $updateResponse = $this->putJson('/api/categorias/'.$id, [
            'name' => 'Vidros Temperados',
        ], $headers);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('name', 'Vidros Temperados');

        $disableResponse = $this->deleteJson('/api/categorias/'.$id, [], $headers);
        $disableResponse->assertNoContent();

        $this->assertDatabaseHas('categories', [
            'id' => $id,
            'name' => 'Vidros Temperados',
            'active' => 0,
        ]);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $headers = $this->authHeader($vendedor);

        $response = $this->postJson('/api/categorias', [
            'name' => 'Ferragens',
        ], $headers);

        $response
            ->assertStatus(403)
            ->assertJsonPath('message', 'Perfil sem permissao para esta acao.');
    }

    public function test_authenticated_user_can_list_and_view_categories(): void
    {
        $operador = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        Category::query()->create(['name' => 'Acessorios', 'active' => true]);
        Category::query()->create(['name' => 'Servicos', 'active' => false]);

        $headers = $this->authHeader($operador);

        $listResponse = $this->getJson('/api/categorias', $headers);
        $listResponse->assertOk();

        $ids = collect($listResponse->json('data'))->pluck('id');
        $firstId = (int) $ids->first();

        $showResponse = $this->getJson('/api/categorias/'.$firstId, $headers);
        $showResponse->assertOk()->assertJsonStructure(['id', 'name', 'active', 'catalog_items_count']);
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
}
