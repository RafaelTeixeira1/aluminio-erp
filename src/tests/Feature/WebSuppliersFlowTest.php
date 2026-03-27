<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebSuppliersFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_deactivate_and_restore_supplier_via_web(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $createResponse = $this->actingAs($admin)->post(route('suppliers.store'), [
            'name' => 'Fornecedor Prisma',
            'document' => '12.345.678/0001-99',
            'email' => 'fornecedor.prisma@example.com',
            'phone' => '(11) 3333-4444',
            'contact_person' => 'Marina',
            'address' => 'Rua da Industria, 100',
            'is_active' => 1,
        ]);

        $createResponse
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Fornecedor criado com sucesso!');

        $supplier = Supplier::query()->where('name', 'Fornecedor Prisma')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('suppliers.index', ['search' => 'Prisma']))
            ->assertOk()
            ->assertSeeText('Fornecedor Prisma');

        $updateResponse = $this->actingAs($admin)->put(route('suppliers.update', $supplier), [
            'name' => 'Fornecedor Prisma Atualizado',
            'document' => '12.345.678/0001-99',
            'email' => 'fornecedor.prisma@example.com',
            'phone' => '(11) 3333-5555',
            'contact_person' => 'Marina',
            'address' => 'Rua da Industria, 200',
            'is_active' => 1,
        ]);

        $updateResponse
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Fornecedor atualizado com sucesso!');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Fornecedor Prisma Atualizado',
            'is_active' => true,
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier));

        $deleteResponse
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Fornecedor desativado com sucesso!');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);

        $restoreResponse = $this->actingAs($admin)->patch(route('suppliers.restore', $supplier));

        $restoreResponse
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Fornecedor reativado com sucesso!');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => true,
        ]);
    }

    public function test_vendedor_cannot_access_suppliers_web_pages(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $this->actingAs($vendedor)
            ->get(route('suppliers.index'))
            ->assertForbidden()
            ->assertSeeText('Acesso negado');
    }
}
