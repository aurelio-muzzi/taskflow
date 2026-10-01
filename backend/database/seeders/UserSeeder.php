<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', RoleEnum::ADMIN)->firstOrFail();
        $managerRole = Role::where('slug', RoleEnum::MANAGER)->firstOrFail();
        $userRole = Role::where('slug', RoleEnum::USER)->firstOrFail();

        // 1. Administrador Padrão
        User::firstOrCreate(
            ['email' => 'admin@taskflow.dev'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Administrador do Sistema',
                'password' => Hash::make('Password123!'),
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        // 2. Gerente Padrão
        User::firstOrCreate(
            ['email' => 'manager@taskflow.dev'],
            [
                'role_id' => $managerRole->id,
                'name' => 'Gerente de Projetos',
                'password' => Hash::make('Password123!'),
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        // 3. Usuário Padrão
        User::firstOrCreate(
            ['email' => 'user@taskflow.dev'],
            [
                'role_id' => $userRole->id,
                'name' => 'Usuário Desenvolvedor',
                'password' => Hash::make('Password123!'),
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );
    }
}
