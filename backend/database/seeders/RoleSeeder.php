<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rolesData = [
            RoleEnum::ADMIN->value => [
                'name' => 'Administrador',
                'description' => 'Acesso irrestrito a usuários, relatórios, projetos e auditoria do sistema.',
            ],
            RoleEnum::MANAGER->value => [
                'name' => 'Gerente',
                'description' => 'Criação e gestão de projetos, atribuição de tarefas e relatórios gerenciais.',
            ],
            RoleEnum::USER->value => [
                'name' => 'Usuário',
                'description' => 'Acesso aos projetos e tarefas atribuídos, atualização de status e comentários.',
            ],
        ];

        $roles = [];
        foreach ($rolesData as $slug => $data) {
            $roles[$slug] = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'description' => $data['description']]
            );
        }

        // Permissões padrão do sistema
        $permissionsData = [
            ['name' => 'Gerenciar Usuários', 'slug' => 'users.manage', 'description' => 'Criar, listar, editar e inativar usuários'],
            ['name' => 'Gerenciar Perfis e Permissões', 'slug' => 'roles.manage', 'description' => 'Visualizar e administrar RBAC'],
            ['name' => 'Criar Projetos', 'slug' => 'projects.create', 'description' => 'Criar novos projetos'],
            ['name' => 'Editar Qualquer Projeto', 'slug' => 'projects.edit_any', 'description' => 'Editar projetos de qualquer proprietário'],
            ['name' => 'Criar Tarefas', 'slug' => 'tasks.create', 'description' => 'Criar tarefas nos projetos participantes'],
            ['name' => 'Atribuir Tarefas', 'slug' => 'tasks.assign', 'description' => 'Atribuir tarefas aos membros'],
            ['name' => 'Moderar Comentários', 'slug' => 'comments.moderate', 'description' => 'Excluir ou moderar comentários de terceiros'],
            ['name' => 'Visualizar Auditoria', 'slug' => 'audit.view', 'description' => 'Visualizar histórico de auditoria global'],
        ];

        $permissions = [];
        foreach ($permissionsData as $permData) {
            $permissions[$permData['slug']] = Permission::firstOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );
        }

        // Associar permissões aos papéis
        // ADMIN possui todas as permissões
        $roles[RoleEnum::ADMIN->value]->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $permissions)));

        // MANAGER possui criação de projetos, tarefas, atribuição e visualização
        $managerPerms = [
            $permissions['projects.create']->id,
            $permissions['tasks.create']->id,
            $permissions['tasks.assign']->id,
        ];
        $roles[RoleEnum::MANAGER->value]->permissions()->sync($managerPerms);

        // USER possui permissão de criar tarefas em seus projetos
        $userPerms = [
            $permissions['tasks.create']->id,
        ];
        $roles[RoleEnum::USER->value]->permissions()->sync($userPerms);
    }
}
