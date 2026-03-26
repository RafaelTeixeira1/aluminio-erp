<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_list_users(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $headers = $this->authHeader($admin);

        $createResponse = $this->postJson('/api/usuarios', [
            'name' => 'Novo Usuario',
            'email' => 'novo.usuario@example.com',
            'password' => 'secret123',
            'profile' => 'vendedor',
            'active' => true,
        ], $headers);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('email', 'novo.usuario@example.com')
            ->assertJsonPath('profile', 'vendedor')
            ->assertJsonPath('active', true);

        $this->assertDatabaseHas('users', [
            'email' => 'novo.usuario@example.com',
            'profile' => 'vendedor',
            'active' => 1,
        ]);

        $listResponse = $this->getJson('/api/usuarios', $headers);
        $listResponse->assertOk();

        $emails = collect($listResponse->json('data'))->pluck('email');
        $this->assertTrue($emails->contains('novo.usuario@example.com'));
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $headers = $this->authHeader($vendedor);

        $response = $this->getJson('/api/usuarios', $headers);

        $response
            ->assertStatus(403)
            ->assertJsonPath('message', 'Perfil sem permissao para esta acao.');
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $headers = $this->authHeader($admin);

        $response = $this->patchJson('/api/usuarios/'.$admin->id.'/status', [
            'active' => false,
        ], $headers);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nao e permitido inativar o proprio usuario logado.');

        $this->assertTrue((bool) $admin->fresh()->active);
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
