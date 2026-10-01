<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrador',
                'slug' => RoleEnum::ADMIN->value,
                'description' => 'Acesso irrestrito a usuários, relatórios, projetos e auditoria do sistema.',
            ],
            [
                'name' => 'Gerente',
                'slug' => RoleEnum::MANAGER->value,
                'description' => 'Criação e gestão de projetos, atribuição de tarefas e relatórios gerenciais.',
            ],
            [
                'name' => 'Usuário',
                'slug' => RoleEnum::USER->value,
                'description' => 'Acesso aos projetos e tarefas atribuídos, atualização de status e comentários.',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
