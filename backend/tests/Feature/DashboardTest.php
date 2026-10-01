<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\RoleEnum;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $user1;

    private User $user2;

    private Project $project1;

    private Project $project2;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Administrador', 'slug' => RoleEnum::ADMIN]);
        $managerRole = Role::create(['name' => 'Gerente', 'slug' => RoleEnum::MANAGER]);
        $userRole = Role::create(['name' => 'Usuário', 'slug' => RoleEnum::USER]);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->manager = User::factory()->create(['role_id' => $managerRole->id]);
        $this->user1 = User::factory()->create(['role_id' => $userRole->id]);
        $this->user2 = User::factory()->create(['role_id' => $userRole->id]);

        $this->project1 = Project::factory()->create([
            'owner_id' => $this->manager->id,
            'name' => 'Projeto Interno Alpha',
            'code' => 'ALPHA',
            'status' => ProjectStatus::ACTIVE,
        ]);

        ProjectMember::create([
            'project_id' => $this->project1->id,
            'user_id' => $this->user1->id,
            'role' => ProjectRole::MEMBER,
        ]);

        $this->project2 = Project::factory()->create([
            'owner_id' => $this->admin->id,
            'name' => 'Projeto Corporativo Beta',
            'code' => 'BETA',
            'status' => ProjectStatus::PLANNING,
        ]);
    }

    public function test_admin_can_retrieve_global_dashboard_metrics(): void
    {
        Task::factory()->create([
            'project_id' => $this->project1->id,
            'status' => TaskStatus::DONE,
            'assigned_to' => $this->user1->id,
        ]);

        Task::factory()->create([
            'project_id' => $this->project2->id,
            'status' => TaskStatus::IN_PROGRESS,
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/metrics');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.projects.total', 2)
            ->assertJsonPath('data.tasks.total', 2)
            ->assertJsonPath('data.tasks.completed', 1)
            ->assertJsonPath('data.tasks.pending', 1);
    }

    public function test_regular_user_only_sees_metrics_from_their_projects(): void
    {
        Task::factory()->create([
            'project_id' => $this->project1->id,
            'status' => TaskStatus::TODO,
            'assigned_to' => $this->user1->id,
        ]);

        Task::factory()->create([
            'project_id' => $this->project2->id,
            'status' => TaskStatus::TODO,
            'assigned_to' => $this->user2->id,
        ]);

        // user1 só pertence ao project1
        $response = $this->actingAs($this->user1)->getJson('/api/v1/dashboard/metrics');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.projects.total', 1)
            ->assertJsonPath('data.tasks.total', 1)
            ->assertJsonPath('data.my_tasks.total', 1);
    }

    public function test_overdue_tasks_are_accurately_counted(): void
    {
        // Tarefa atrasada
        Task::factory()->create([
            'project_id' => $this->project1->id,
            'status' => TaskStatus::IN_PROGRESS,
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'assigned_to' => $this->user1->id,
        ]);

        // Tarefa no prazo
        Task::factory()->create([
            'project_id' => $this->project1->id,
            'status' => TaskStatus::TODO,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'assigned_to' => $this->user1->id,
        ]);

        // Tarefa concluída atrasada não deve ser contabilizada como atrasada pendente
        Task::factory()->create([
            'project_id' => $this->project1->id,
            'status' => TaskStatus::DONE,
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'assigned_to' => $this->user1->id,
        ]);

        $response = $this->actingAs($this->user1)->getJson('/api/v1/dashboard/metrics');

        $response->assertOk()
            ->assertJsonPath('data.tasks.overdue', 1)
            ->assertJsonPath('data.my_tasks.overdue', 1);
    }

    public function test_global_search_returns_matching_results(): void
    {
        Task::factory()->create([
            'project_id' => $this->project1->id,
            'title' => 'Refatoração da Arquitetura de Redes',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/search?q=Arquitetura');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.tasks')
            ->assertJsonPath('data.tasks.0.title', 'Refatoração da Arquitetura de Redes');
    }

    public function test_global_search_respects_user_access_scope(): void
    {
        Task::factory()->create([
            'project_id' => $this->project2->id,
            'title' => 'Demanda Confidencial Beta',
        ]);

        // user1 não tem acesso ao project2
        $response = $this->actingAs($this->user1)->getJson('/api/v1/search?q=Confidencial');

        $response->assertOk()
            ->assertJsonCount(0, 'data.tasks');
    }
}
