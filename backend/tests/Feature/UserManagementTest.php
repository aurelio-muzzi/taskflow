<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $regularUser;

    private Role $adminRole;

    private Role $managerRole;

    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(
            ['slug' => RoleEnum::ADMIN->value],
            ['name' => 'Administrador', 'description' => 'Acesso total.']
        );

        $this->managerRole = Role::firstOrCreate(
            ['slug' => RoleEnum::MANAGER->value],
            ['name' => 'Gerente', 'description' => 'Gestão de projetos.']
        );

        $this->userRole = Role::firstOrCreate(
            ['slug' => RoleEnum::USER->value],
            ['name' => 'Usuário', 'description' => 'Acesso comum.']
        );

        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $this->manager = User::factory()->create(['role_id' => $this->managerRole->id]);
        $this->regularUser = User::factory()->create(['role_id' => $this->userRole->id]);
    }

    public function test_admin_can_list_users_with_pagination(): void
    {
        User::factory()->count(10)->create(['role_id' => $this->userRole->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/users?per_page=5');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => ['id', 'name', 'email', 'status', 'role'],
                    ],
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data.items'));
    }

    public function test_admin_can_search_and_filter_users(): void
    {
        User::factory()->create([
            'role_id' => $this->userRole->id,
            'name' => 'Carlos Silva',
            'email' => 'carlos@silva.com',
            'status' => UserStatus::ACTIVE,
        ]);

        User::factory()->create([
            'role_id' => $this->userRole->id,
            'name' => 'Maria Oliveira',
            'email' => 'maria@oliveira.com',
            'status' => UserStatus::INACTIVE,
        ]);

        // Busca por nome "Carlos"
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/users?q=Carlos');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals('Carlos Silva', $response->json('data.items.0.name'));

        // Filtro por status inativo
        $responseInactive = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/users?status=INACTIVE');
        $responseInactive->assertStatus(200);
        $this->assertCount(1, $responseInactive->json('data.items'));
        $this->assertEquals('Maria Oliveira', $responseInactive->json('data.items.0.name'));
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $responseManager = $this->actingAs($this->manager, 'sanctum')->getJson('/api/v1/users');
        $responseManager->assertStatus(403);

        $responseUser = $this->actingAs($this->regularUser, 'sanctum')->getJson('/api/v1/users');
        $responseUser->assertStatus(403);
    }

    public function test_admin_can_create_new_user(): void
    {
        $payload = [
            'role_id' => $this->userRole->id,
            'name' => 'Novo Funcionário',
            'email' => 'novo@taskflow.dev',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'ACTIVE',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/users', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Novo Funcionário',
                    'email' => 'novo@taskflow.dev',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'novo@taskflow.dev',
            'name' => 'Novo Funcionário',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $targetUser = User::factory()->create(['role_id' => $this->userRole->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/users/{$targetUser->id}", [
            'name' => 'Nome Alterado',
            'role_id' => $this->managerRole->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $targetUser->id,
                    'name' => 'Nome Alterado',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Nome Alterado',
            'role_id' => $this->managerRole->id,
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $targetUser = User::factory()->create([
            'role_id' => $this->userRole->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/users/{$targetUser->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'INACTIVE',
                ],
            ]);

        $this->assertEquals(UserStatus::INACTIVE, $targetUser->fresh()->status);
    }

    public function test_admin_cannot_toggle_own_status(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/users/{$this->admin->id}/toggle-status");

        $response->assertStatus(403);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $targetUser = User::factory()->create(['role_id' => $this->userRole->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Usuário removido com sucesso.',
            ]);

        // Valida exclusão lógica
        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->admin->id}");

        $response->assertStatus(403);
    }
}
