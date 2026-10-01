<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
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

        // Projeto 1: TaskFlow Core
        $project1 = Project::firstOrCreate(
            ['code' => 'TASKFLOW'],
            [
                'owner_id' => $manager->id,
                'name' => 'TaskFlow Core Engine',
                'description' => 'Desenvolvimento da plataforma central de gestão e orquestração de tarefas.',
                'status' => ProjectStatus::ACTIVE,
                'start_date' => now()->subDays(15)->format('Y-m-d'),
                'due_date' => now()->addDays(45)->format('Y-m-d'),
            ]
        );

        $project1->memberRecords()->firstOrCreate(
            ['user_id' => $manager->id],
            ['role' => ProjectRole::OWNER]
        );
        $project1->memberRecords()->firstOrCreate(
            ['user_id' => $user->id],
            ['role' => ProjectRole::MEMBER]
        );
        $project1->memberRecords()->firstOrCreate(
            ['user_id' => $admin->id],
            ['role' => ProjectRole::MANAGER]
        );

        // Projeto 2: Portal do Cliente
        $project2 = Project::firstOrCreate(
            ['code' => 'PORTAL'],
            [
                'owner_id' => $admin->id,
                'name' => 'Portal Corporativo',
                'description' => 'Interface para acompanhamento externo de entregáveis e chamados.',
                'status' => ProjectStatus::PLANNING,
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'due_date' => now()->addDays(60)->format('Y-m-d'),
            ]
        );

        $project2->memberRecords()->firstOrCreate(
            ['user_id' => $admin->id],
            ['role' => ProjectRole::OWNER]
        );
        $project2->memberRecords()->firstOrCreate(
            ['user_id' => $manager->id],
            ['role' => ProjectRole::MANAGER]
        );
        $project2->memberRecords()->firstOrCreate(
            ['user_id' => $user->id],
            ['role' => ProjectRole::VIEWER]
        );
    }
}
