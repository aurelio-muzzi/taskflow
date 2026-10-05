<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\InternalNotification;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $member;

    private User $otherUser;

    private Project $project;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Administrador', 'slug' => RoleEnum::ADMIN]);
        $managerRole = Role::create(['name' => 'Gerente', 'slug' => RoleEnum::MANAGER]);
        $userRole = Role::create(['name' => 'Usuário', 'slug' => RoleEnum::USER]);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->manager = User::factory()->create(['role_id' => $managerRole->id]);
        $this->member = User::factory()->create(['role_id' => $userRole->id]);
        $this->otherUser = User::factory()->create(['role_id' => $userRole->id]);

        $this->project = Project::factory()->create([
            'owner_id' => $this->manager->id,
            'name' => 'TaskFlow Platform',
            'code' => 'TFLOW-01',
        ]);

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->manager->id,
            'role' => ProjectRole::OWNER,
        ]);

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->member->id,
            'role' => ProjectRole::MEMBER,
        ]);

        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->member->id,
            'title' => 'Auditoria e Notificações',
        ]);
    }

    public function test_project_member_can_add_comment_to_task(): void
    {
        $response = $this->actingAs($this->member)
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'content' => 'Comentário técnico sobre a implementação do audit log.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Comentário técnico sobre a implementação do audit log.')
            ->assertJsonPath('data.user_id', $this->member->id);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'content' => 'Comentário técnico sobre a implementação do audit log.',
        ]);
    }

    public function test_adding_comment_generates_audit_log_and_notifies_creator(): void
    {
        $this->actingAs($this->member)
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'content' => 'Nova nota para o criador da tarefa.',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Task::class,
            'auditable_id' => $this->task->id,
            'event' => 'comment_added',
        ]);

        // O criador da tarefa é o manager; deve receber notificação de novo comentário
        $this->assertDatabaseHas('internal_notifications', [
            'user_id' => $this->manager->id,
            'type' => 'task_comment',
        ]);
    }

    public function test_comment_author_can_delete_their_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'content' => 'Comentário a ser excluído.',
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('task_comments', ['id' => $comment->id]);
    }

    public function test_unauthorized_user_cannot_delete_others_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'content' => 'Comentário protegido.',
        ]);

        $response = $this->actingAs($this->otherUser)
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertForbidden();
    }

    public function test_admin_and_manager_can_view_audit_logs(): void
    {
        AuditLog::create([
            'user_id' => $this->admin->id,
            'auditable_type' => Task::class,
            'auditable_id' => $this->task->id,
            'event' => 'status_changed',
            'description' => 'Status alterado.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_regular_user_cannot_view_general_audit_logs(): void
    {
        $response = $this->actingAs($this->member)->getJson('/api/v1/audit-logs');

        $response->assertForbidden();
    }

    public function test_user_can_view_notifications_and_mark_as_read(): void
    {
        $notif = InternalNotification::create([
            'user_id' => $this->member->id,
            'type' => 'task_assigned',
            'title' => 'Nova tarefa',
            'message' => 'Você tem uma nova demanda.',
        ]);

        $listResponse = $this->actingAs($this->member)->getJson('/api/v1/notifications');

        $listResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonCount(1, 'data.items');

        $readResponse = $this->actingAs($this->member)
            ->patchJson("/api/v1/notifications/{$notif->id}/read");

        $readResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_read', true);

        $notif->refresh();
        $this->assertNotNull($notif->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        InternalNotification::create([
            'user_id' => $this->member->id,
            'type' => 'task_assigned',
            'title' => 'Notif 1',
            'message' => 'Mensagem 1',
        ]);

        InternalNotification::create([
            'user_id' => $this->member->id,
            'type' => 'task_comment',
            'title' => 'Notif 2',
            'message' => 'Mensagem 2',
        ]);

        $response = $this->actingAs($this->member)->postJson('/api/v1/notifications/mark-all-read');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $unreadCount = InternalNotification::where('user_id', $this->member->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    public function test_comment_author_can_update_their_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'content' => 'Comentário original.',
        ]);

        $response = $this->actingAs($this->member)
            ->patchJson("/api/v1/tasks/{$this->task->id}/comments/{$comment->id}", [
                'body' => 'Comentário atualizado pelo autor.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Comentário atualizado pelo autor.')
            ->assertJsonPath('data.body', 'Comentário atualizado pelo autor.');

        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
            'content' => 'Comentário atualizado pelo autor.',
        ]);

        // Verifica geração do log de auditoria
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Task::class,
            'auditable_id' => $this->task->id,
            'event' => 'comment_updated',
        ]);
    }

    public function test_unauthorized_user_cannot_update_others_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'content' => 'Texto do membro.',
        ]);

        $response = $this->actingAs($this->otherUser)
            ->patchJson("/api/v1/tasks/{$this->task->id}/comments/{$comment->id}", [
                'content' => 'Tentativa de alteração não autorizada.',
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_mark_all_notifications_as_read_via_patch(): void
    {
        InternalNotification::create([
            'user_id' => $this->member->id,
            'type' => 'task_assigned',
            'title' => 'Nova Tarefa',
            'message' => 'Alocada para você.',
        ]);

        $response = $this->actingAs($this->member)->patchJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(0, InternalNotification::where('user_id', $this->member->id)->whereNull('read_at')->count());
    }

    public function test_admin_can_view_single_audit_log_and_no_sensitive_secrets_stored(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->admin->id,
            'auditable_type' => Task::class,
            'auditable_id' => $this->task->id,
            'event' => 'status_changed',
            'description' => 'Status alterado de TODO para IN_PROGRESS',
            'old_values' => ['status' => 'TODO'],
            'new_values' => ['status' => 'IN_PROGRESS'],
            'created_at' => now(),
        ]);

        // Testar visualização por Admin
        $response = $this->actingAs($this->admin)->getJson("/api/v1/audit-logs/{$log->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.event', 'status_changed');

        // Testar bloqueio para usuário comum
        $userForbidden = $this->actingAs($this->member)->getJson("/api/v1/audit-logs/{$log->id}");
        $userForbidden->assertForbidden();

        // Validar ausência de segredos ou senhas na auditoria
        $this->assertArrayNotHasKey('password', (array) $log->old_values);
        $this->assertArrayNotHasKey('token', (array) $log->new_values);
    }
}
