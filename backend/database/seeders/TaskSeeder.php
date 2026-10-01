<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
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

        $project1 = Project::where('code', 'TASKFLOW')->first();
        $project2 = Project::where('code', 'PORTAL')->first();

        if ($project1) {
            Task::firstOrCreate(
                [
                    'project_id' => $project1->id,
                    'title' => 'Configurar arquitetura do banco e migrations',
                ],
                [
                    'description' => 'Modelar tabelas, índices e relacionamentos no PostgreSQL 16.',
                    'status' => TaskStatus::DONE,
                    'priority' => TaskPriority::HIGH,
                    'assigned_to' => $user->id,
                    'created_by' => $manager->id,
                    'due_date' => now()->subDays(5)->format('Y-m-d'),
                    'estimated_hours' => 8.0,
                    'order' => 1,
                    'completed_at' => now()->subDays(5),
                ]
            );

            Task::firstOrCreate(
                [
                    'project_id' => $project1->id,
                    'title' => 'Implementar autenticação Sanctum e RBAC',
                ],
                [
                    'description' => 'Criar fluxo completo de login, validação de token e políticas de autorização.',
                    'status' => TaskStatus::DONE,
                    'priority' => TaskPriority::URGENT,
                    'assigned_to' => $manager->id,
                    'created_by' => $admin->id,
                    'due_date' => now()->subDays(2)->format('Y-m-d'),
                    'estimated_hours' => 12.0,
                    'order' => 2,
                    'completed_at' => now()->subDays(2),
                ]
            );

            Task::firstOrCreate(
                [
                    'project_id' => $project1->id,
                    'title' => 'Construir API REST e Actions para Gestão de Tarefas',
                ],
                [
                    'description' => 'Desenvolver endpoints RESTful, DTOs, Policies e Form Requests para tasks.',
                    'status' => TaskStatus::IN_PROGRESS,
                    'priority' => TaskPriority::HIGH,
                    'assigned_to' => $user->id,
                    'created_by' => $manager->id,
                    'due_date' => now()->addDays(3)->format('Y-m-d'),
                    'estimated_hours' => 16.0,
                    'order' => 3,
                ]
            );

            Task::firstOrCreate(
                [
                    'project_id' => $project1->id,
                    'title' => 'Refatorar UI do Kanban com drag-and-drop',
                ],
                [
                    'description' => 'Implementar cards interativos com transição suave de colunas no Vue 3.',
                    'status' => TaskStatus::TODO,
                    'priority' => TaskPriority::MEDIUM,
                    'assigned_to' => $user->id,
                    'created_by' => $manager->id,
                    'due_date' => now()->addDays(7)->format('Y-m-d'),
                    'estimated_hours' => 10.0,
                    'order' => 4,
                ]
            );

            Task::firstOrCreate(
                [
                    'project_id' => $project1->id,
                    'title' => 'Revisar cobertura de testes e relatórios de auditoria',
                ],
                [
                    'description' => 'Garantir cobertura abrangente de testes de feature e validação do Pint.',
                    'status' => TaskStatus::REVIEW,
                    'priority' => TaskPriority::LOW,
                    'assigned_to' => $manager->id,
                    'created_by' => $admin->id,
                    'due_date' => now()->addDays(10)->format('Y-m-d'),
                    'estimated_hours' => 6.0,
                    'order' => 5,
                ]
            );
        }

        if ($project2) {
            Task::firstOrCreate(
                [
                    'project_id' => $project2->id,
                    'title' => 'Levantamento de requisitos do Portal',
                ],
                [
                    'description' => 'Mapear necessidades dos clientes para emissão de relatórios e chamados.',
                    'status' => TaskStatus::IN_PROGRESS,
                    'priority' => TaskPriority::HIGH,
                    'assigned_to' => $manager->id,
                    'created_by' => $admin->id,
                    'due_date' => now()->addDays(5)->format('Y-m-d'),
                    'estimated_hours' => 14.0,
                    'order' => 1,
                ]
            );
        }
    }
}
