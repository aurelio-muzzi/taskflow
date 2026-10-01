<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\RoleEnum;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $user1;

    private User $user2;

    private Project $project;

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

        $this->project = Project::factory()->create([
            'owner_id' => $this->manager->id,
            'name' => 'Projeto Alpha',
            'code' => 'ALPHA-01',
        ]);

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user1->id,
            'role' => ProjectRole::MEMBER,
        ]);
    }

    public function test_admin_can_list_all_tasks(): void
    {
        Task::factory()->count(3)->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_user_only_sees_tasks_from_their_projects(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Tarefa Visível',
        ]);

        $otherProject = Project::factory()->create(['owner_id' => $this->admin->id]);
        Task::factory()->create([
            'project_id' => $otherProject->id,
            'title' => 'Tarefa Invisível',
        ]);

        $response = $this->actingAs($this->user1)->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.title', 'Tarefa Visível');
    }

    public function test_project_member_can_create_task(): void
    {
        $payload = [
            'title' => 'Nova Tarefa no Projeto',
            'description' => 'Descrição detalhada da tarefa.',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::HIGH->value,
            'assigned_to' => $this->user1->id,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'estimated_hours' => 6.5,
        ];

        $response = $this->actingAs($this->user1)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Nova Tarefa no Projeto')
            ->assertJsonPath('data.priority', TaskPriority::HIGH->value);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $this->project->id,
            'title' => 'Nova Tarefa no Projeto',
            'created_by' => $this->user1->id,
            'assigned_to' => $this->user1->id,
        ]);
    }

    public function test_viewer_cannot_create_task(): void
    {
        $viewer = User::factory()->create(['role_id' => $this->user1->role_id]);
        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $viewer->id,
            'role' => ProjectRole::VIEWER,
        ]);

        $payload = [
            'title' => 'Tentativa de Criar Tarefa',
        ];

        $response = $this->actingAs($viewer)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->user1)->getJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_unauthorized_user_cannot_view_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
        ]);

        // user2 não faz parte do projeto
        $response = $this->actingAs($this->user2)->getJson("/api/v1/tasks/{$task->id}");

        $response->assertForbidden();
    }

    public function test_assignee_can_update_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->user1->id,
            'title' => 'Título Antigo',
        ]);

        $response = $this->actingAs($this->user1)->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Título Atualizado pelo Responsável',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Título Atualizado pelo Responsável');
    }

    public function test_assignee_can_update_status_and_completion_date_is_set(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->user1->id,
            'status' => TaskStatus::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user1)->patchJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::DONE->value,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', TaskStatus::DONE->value);

        $task->refresh();
        $this->assertEquals(TaskStatus::DONE, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_manager_can_delete_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user1->id,
        ]);

        $response = $this->actingAs($this->manager)->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_regular_member_cannot_delete_task_they_did_not_create(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->user1->id,
        ]);

        $response = $this->actingAs($this->user1)->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }

    public function test_filtering_tasks_by_status_and_priority(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
            'priority' => TaskPriority::LOW,
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks?status=in_progress&priority=high");

        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', TaskStatus::IN_PROGRESS->value)
            ->assertJsonPath('data.items.0.priority', TaskPriority::HIGH->value);
    }

    public function test_project_member_can_reorder_tasks_and_transition_status(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
            'order' => 1,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
            'order' => 2,
        ]);

        $payload = [
            'tasks' => [
                [
                    'id' => $task1->id,
                    'order' => 2,
                    'status' => TaskStatus::IN_PROGRESS->value,
                ],
                [
                    'id' => $task2->id,
                    'order' => 1,
                    'status' => TaskStatus::TODO->value,
                ],
            ],
        ];

        $response = $this->actingAs($this->user1)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks/reorder", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $task1->refresh();
        $task2->refresh();

        $this->assertEquals(TaskStatus::IN_PROGRESS, $task1->status);
        $this->assertEquals(2, $task1->order);

        $this->assertEquals(TaskStatus::TODO, $task2->status);
        $this->assertEquals(1, $task2->order);
    }

    public function test_viewer_cannot_reorder_project_tasks(): void
    {
        $viewer = User::factory()->create(['role_id' => $this->user1->role_id]);
        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $viewer->id,
            'role' => ProjectRole::VIEWER,
        ]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
            'order' => 1,
        ]);

        $response = $this->actingAs($viewer)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks/reorder", [
                'tasks' => [
                    ['id' => $task->id, 'order' => 2, 'status' => TaskStatus::IN_PROGRESS->value],
                ],
            ]);

        $response->assertForbidden();
    }
}
