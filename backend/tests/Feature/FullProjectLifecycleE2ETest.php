<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\RoleEnum;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullProjectLifecycleE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $developer;

    private User $viewer;

    private User $externalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $managerRole = Role::where('slug', RoleEnum::MANAGER->value)->firstOrFail();
        $userRole = Role::where('slug', RoleEnum::USER->value)->firstOrFail();

        $this->manager = User::factory()->create([
            'role_id' => $managerRole->id,
            'name' => 'Gerente de Projetos',
            'email' => 'gerente.e2e@taskflow.local',
        ]);

        $this->developer = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => 'Desenvolvedor Fullstack',
            'email' => 'dev.e2e@taskflow.local',
        ]);

        $this->viewer = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => 'Auditor Visualizador',
            'email' => 'viewer.e2e@taskflow.local',
        ]);

        $this->externalUser = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => 'Usuário Externo',
            'email' => 'externo.e2e@taskflow.local',
        ]);
    }

    public function test_complete_project_lifecycle_end_to_end(): void
    {
        // 1. Autenticação e obtenção de token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'gerente.e2e@taskflow.local',
            'password' => 'password',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('success', true);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // 2. Criação do Projeto
        $projectPayload = [
            'name' => 'TaskFlow Enterprise Release',
            'code' => 'TFE-01',
            'description' => 'Projeto estratégico corporativo para modernização da plataforma.',
            'status' => ProjectStatus::ACTIVE->value,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ];

        $createProjectRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/projects', $projectPayload);

        $createProjectRes->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'TFE-01');

        $projectId = $createProjectRes->json('data.id');

        // 3. Montagem da Equipe (Adicionar Desenvolvedor e Visualizador)
        $addMemberRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/projects/{$projectId}/members", [
                'user_id' => $this->developer->id,
                'role' => ProjectRole::MEMBER->value,
            ]);

        $addMemberRes->assertCreated()
            ->assertJsonPath('data.role', ProjectRole::MEMBER->value);

        $addViewerRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/projects/{$projectId}/members", [
                'user_id' => $this->viewer->id,
                'role' => ProjectRole::VIEWER->value,
            ]);

        $addViewerRes->assertCreated()
            ->assertJsonPath('data.role', ProjectRole::VIEWER->value);

        // 4. Criação da Demanda / Tarefa
        $taskPayload = [
            'title' => 'Implementar módulo de segurança OAuth2 e MFA',
            'description' => 'Configurar suporte a múltiplos fatores de autenticação corporativa.',
            'priority' => TaskPriority::URGENT->value,
            'assigned_to' => $this->developer->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'estimated_hours' => 16.0,
        ];

        $createTaskRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/projects/{$projectId}/tasks", $taskPayload);

        $createTaskRes->assertCreated()
            ->assertJsonPath('data.status', TaskStatus::TODO->value)
            ->assertJsonPath('data.priority', TaskPriority::URGENT->value);

        $taskId = $createTaskRes->json('data.id');

        // 5. Desenvolvedor assume a tarefa e transiciona para Em Andamento no Kanban
        $devStatusRes = $this->actingAs($this->developer)
            ->patchJson("/api/v1/tasks/{$taskId}/status", [
                'status' => TaskStatus::IN_PROGRESS->value,
                'order' => 0,
            ]);

        $devStatusRes->assertOk()
            ->assertJsonPath('data.status', TaskStatus::IN_PROGRESS->value);

        // 6. Colaboração via Comentários
        $commentRes = $this->actingAs($this->manager)
            ->postJson("/api/v1/tasks/{$taskId}/comments", [
                'content' => 'Lembrar de adicionar testes para tokens revogados.',
            ]);

        $commentRes->assertCreated()
            ->assertJsonPath('data.content', 'Lembrar de adicionar testes para tokens revogados.');

        // O desenvolvedor responde o comentário
        $devReplyRes = $this->actingAs($this->developer)
            ->postJson("/api/v1/tasks/{$taskId}/comments", [
                'content' => 'Entendido! Cobertura implementada com 100% dos cenários.',
            ]);

        $devReplyRes->assertCreated();

        // 7. Reordenação e Conclusão via Kanban Batch
        $reorderRes = $this->actingAs($this->developer)
            ->postJson("/api/v1/projects/{$projectId}/tasks/reorder", [
                'tasks' => [
                    [
                        'id' => $taskId,
                        'order' => 1,
                        'status' => TaskStatus::DONE->value,
                    ],
                ],
            ]);

        $reorderRes->assertOk()
            ->assertJsonPath('success', true);

        /** @var Task $taskModel */
        $taskModel = Task::findOrFail($taskId);
        $this->assertEquals(TaskStatus::DONE, $taskModel->status);
        $this->assertNotNull($taskModel->completed_at);

        // 8. Verificação de Auditoria e Notificações
        $auditRes = $this->actingAs($this->manager)->getJson("/api/v1/tasks/{$taskId}/audit-logs");
        $auditRes->assertOk();
        $this->assertGreaterThanOrEqual(1, count($auditRes->json('data')));

        $notifRes = $this->actingAs($this->developer)->getJson('/api/v1/notifications');
        $notifRes->assertOk();

        // 9. Agregação Executiva no Dashboard
        $dashboardRes = $this->actingAs($this->manager)->getJson('/api/v1/dashboard/metrics');
        $dashboardRes->assertOk()
            ->assertJsonPath('data.projects.active', 1)
            ->assertJsonPath('data.tasks.completed', 1)
            ->assertJsonPath('data.tasks.completion_rate', 100);

        // 10. Busca Global Unificada
        $searchRes = $this->actingAs($this->developer)->getJson('/api/v1/search?q=OAuth2');
        $searchRes->assertOk()
            ->assertJsonCount(1, 'data.tasks');

        // 11. Fronteiras de Segurança e RBAC
        // Usuário externo não pertencente ao projeto não pode ver a tarefa
        $this->actingAs($this->externalUser)
            ->getJson("/api/v1/tasks/{$taskId}")
            ->assertForbidden();

        // Visualizador (VIEWER) não pode alterar status nem excluir
        $this->actingAs($this->viewer)
            ->patchJson("/api/v1/tasks/{$taskId}/status", [
                'status' => TaskStatus::TODO->value,
            ])
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->deleteJson("/api/v1/tasks/{$taskId}")
            ->assertForbidden();
    }
}
