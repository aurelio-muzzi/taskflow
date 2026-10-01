<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\InternalNotification;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentAndNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@taskflow.dev')->first();
        $manager = User::where('email', 'manager@taskflow.dev')->first();
        $user = User::where('email', 'user@taskflow.dev')->first();

        if (! $manager || ! $admin || ! $user) {
            return;
        }

        $tasks = Task::limit(3)->get();

        if ($tasks->isNotEmpty()) {
            $task1 = $tasks[0];

            TaskComment::firstOrCreate(
                [
                    'task_id' => $task1->id,
                    'user_id' => $manager->id,
                    'content' => 'Iniciamos os testes de carga e validação das queries indexadas.',
                ]
            );

            TaskComment::firstOrCreate(
                [
                    'task_id' => $task1->id,
                    'user_id' => $user->id,
                    'content' => 'Migrations aplicadas com sucesso e índices compostos conferidos no PostgreSQL 16.',
                ]
            );

            AuditLog::create([
                'user_id' => $manager->id,
                'auditable_type' => Task::class,
                'auditable_id' => $task1->id,
                'event' => 'status_changed',
                'description' => "Status alterado de 'A Fazer' para 'Em Progresso'.",
                'old_values' => ['status' => 'todo'],
                'new_values' => ['status' => 'in_progress'],
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subHours(4),
            ]);
        }

        // Notificações demonstrativas
        InternalNotification::firstOrCreate(
            ['title' => 'Nova tarefa atribuída'],
            [
                'user_id' => $user->id,
                'type' => 'task_assigned',
                'message' => "Você foi designado para a tarefa 'Construir API REST e Actions para Gestão de Tarefas'.",
                'data' => ['task_id' => $tasks[0]?->id],
                'created_at' => now()->subHours(2),
            ]
        );

        InternalNotification::firstOrCreate(
            ['title' => 'Novo comentário na demanda'],
            [
                'user_id' => $user->id,
                'type' => 'task_comment',
                'message' => 'O gerente adicionou observações técnicas sobre os testes de carga.',
                'data' => ['task_id' => $tasks[0]?->id],
                'created_at' => now()->subMinutes(45),
            ]
        );
    }
}
