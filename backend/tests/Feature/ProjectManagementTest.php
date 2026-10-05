<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\RoleEnum;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $regularUser;

    private User $externalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => RoleEnum::ADMIN->value], ['name' => 'Admin']);
        $managerRole = Role::firstOrCreate(['slug' => RoleEnum::MANAGER->value], ['name' => 'Manager']);
        $userRole = Role::firstOrCreate(['slug' => RoleEnum::USER->value], ['name' => 'User']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->manager = User::factory()->create(['role_id' => $managerRole->id]);
        $this->regularUser = User::factory()->create(['role_id' => $userRole->id]);
        $this->externalUser = User::factory()->create(['role_id' => $userRole->id]);
    }

    public function test_admin_can_list_all_projects(): void
    {
        Project::factory()->count(3)->create(['owner_id' => $this->manager->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => ['id', 'name', 'code', 'status', 'owner'],
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data.items'));
    }

    public function test_user_only_sees_projects_they_belong_to(): void
    {
        // Projeto 1: usuário é membro
        $proj1 = Project::factory()->create(['owner_id' => $this->manager->id]);
        $proj1->memberRecords()->create([
            'user_id' => $this->regularUser->id,
            'role' => ProjectRole::MEMBER,
        ]);

        // Projeto 2: usuário NÃO é membro
        Project::factory()->create(['owner_id' => $this->manager->id]);

        $response = $this->actingAs($this->regularUser, 'sanctum')->getJson('/api/v1/projects');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals($proj1->id, $response->json('data.items.0.id'));
    }

    public function test_manager_can_create_project(): void
    {
        $payload = [
            'name' => 'Novo Sistema ERP',
            'code' => 'ERP-2026',
            'description' => 'Módulo fiscal e financeiro',
            'status' => 'PLANNING',
            'start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addMonths(3)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->manager, 'sanctum')->postJson('/api/v1/projects', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Novo Sistema ERP',
                    'code' => 'ERP-2026',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'code' => 'ERP-2026',
            'owner_id' => $this->manager->id,
        ]);

        // Verifica se o criador foi registrado como OWNER na equipe
        $this->assertDatabaseHas('project_members', [
            'user_id' => $this->manager->id,
            'role' => 'OWNER',
        ]);
    }

    public function test_regular_user_cannot_create_project(): void
    {
        $payload = [
            'name' => 'Tentativa Não Autorizada',
            'code' => 'TEST-01',
        ];

        $response = $this->actingAs($this->regularUser, 'sanctum')->postJson('/api/v1/projects', $payload);
        $response->assertStatus(403);
    }

    public function test_project_code_must_be_unique(): void
    {
        Project::factory()->create(['code' => 'DUPLICATE-CODE', 'owner_id' => $this->manager->id]);

        $payload = [
            'name' => 'Outro Projeto',
            'code' => 'DUPLICATE-CODE',
        ];

        $response = $this->actingAs($this->manager, 'sanctum')->postJson('/api/v1/projects', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_authorized_user_can_view_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);
        $project->memberRecords()->create([
            'user_id' => $this->regularUser->id,
            'role' => ProjectRole::VIEWER,
        ]);

        $response = $this->actingAs($this->regularUser, 'sanctum')->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'current_user_role' => 'VIEWER',
                ],
            ]);
    }

    public function test_unauthorized_user_cannot_view_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);

        $response = $this->actingAs($this->externalUser, 'sanctum')->getJson("/api/v1/projects/{$project->id}");
        $response->assertStatus(403);
    }

    public function test_owner_can_update_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);
        $project->memberRecords()->create([
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')->putJson("/api/v1/projects/{$project->id}", [
            'name' => 'Nome Atualizado',
            'status' => 'ACTIVE',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Nome Atualizado',
                    'status' => 'ACTIVE',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Nome Atualizado',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_owner_can_soft_delete_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);
        $project->memberRecords()->create([
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Projeto removido com sucesso.',
            ]);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_manager_can_add_member_to_team(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);
        $project->memberRecords()->create([
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v1/projects/{$project->id}/members", [
                'user_id' => $this->regularUser->id,
                'role' => 'MEMBER',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $this->regularUser->id,
                    'role' => 'MEMBER',
                ],
            ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->regularUser->id,
            'role' => 'MEMBER',
        ]);
    }

    public function test_manager_can_remove_member_from_team(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);
        $project->memberRecords()->create([
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);
        $project->memberRecords()->create([
            'user_id' => $this->regularUser->id,
            'role' => ProjectRole::MEMBER,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}/members/{$this->regularUser->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->regularUser->id,
        ]);
    }

    public function test_cannot_remove_project_owner_from_team(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);
        $project->memberRecords()->create([
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}/members/{$this->manager->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_cannot_assign_inactive_user_as_project_owner(): void
    {
        $inactiveUser = User::factory()->create([
            'role_id' => $this->manager->role_id,
            'status' => \App\Enums\UserStatus::INACTIVE,
        ]);

        $payload = [
            'name' => 'Projeto com Dono Inativo',
            'code' => 'INACTIVE-OWNER',
            'owner_id' => $inactiveUser->id,
        ];

        $response = $this->actingAs($this->manager, 'sanctum')->postJson('/api/v1/projects', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['owner_id']);
    }

    public function test_owner_can_patch_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id, 'status' => 'PLANNING']);
        $project->memberRecords()->create([
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')->patchJson("/api/v1/projects/{$project->id}", [
            'status' => 'ACTIVE',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'status' => 'ACTIVE',
                ],
            ]);

        $this->assertEquals('ACTIVE', $project->fresh()->status->value);
    }

    public function test_manager_can_patch_member_role(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->manager->id]);
        $project->memberRecords()->create([
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);
        $project->memberRecords()->create([
            'user_id' => $this->regularUser->id,
            'role' => ProjectRole::VIEWER,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->patchJson("/api/v1/projects/{$project->id}/members/{$this->regularUser->id}", [
                'role' => 'MEMBER',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $this->regularUser->id,
                    'role' => 'MEMBER',
                ],
            ]);

        $this->assertEquals(ProjectRole::MEMBER, $project->fresh()->getUserRole($this->regularUser));
    }

    public function test_project_listing_contains_standardized_meta(): void
    {
        Project::factory()->create(['owner_id' => $this->manager->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items',
                    'pagination',
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }
}
