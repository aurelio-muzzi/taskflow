<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o endpoint de health check responde com sucesso.
     */
    public function test_health_check_endpoint_is_operational(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'healthy',
                ],
            ]);
    }

    /**
     * Testa a criação de roles, permissions e a verificação no model User.
     */
    public function test_rbac_database_foundation_and_user_permissions(): void
    {
        // 1. Criar Role
        $roleManager = Role::create([
            'name' => 'Manager',
            'slug' => RoleEnum::MANAGER->value,
            'description' => 'Project Manager',
        ]);

        // 2. Criar Permissions
        $permProjectsCreate = Permission::create([
            'name' => 'Create Projects',
            'slug' => 'projects.create',
            'description' => 'Allows creating projects',
        ]);

        $permTasksCreate = Permission::create([
            'name' => 'Create Tasks',
            'slug' => 'tasks.create',
            'description' => 'Allows creating tasks',
        ]);

        // 3. Vincular permissões à role
        $roleManager->permissions()->attach([$permProjectsCreate->id, $permTasksCreate->id]);

        $this->assertCount(2, $roleManager->fresh()->permissions);

        // 4. Criar usuário com a role Manager
        $managerUser = User::factory()->create([
            'role_id' => $roleManager->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertTrue($managerUser->hasPermission('projects.create'));
        $this->assertTrue($managerUser->hasPermission('tasks.create'));
        $this->assertFalse($managerUser->hasPermission('system.audit'));

        // 5. Testar bypass do Admin
        $roleAdmin = Role::create([
            'name' => 'Administrator',
            'slug' => RoleEnum::ADMIN->value,
            'description' => 'System Administrator',
        ]);

        $adminUser = User::factory()->create([
            'role_id' => $roleAdmin->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertTrue($adminUser->hasPermission('any.arbitrary.permission'));
    }
}
