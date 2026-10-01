<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => fn () => Role::firstOrCreate(
                ['slug' => RoleEnum::USER->value],
                ['name' => 'Usuário', 'description' => 'Perfil de usuário comum.']
            )->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'avatar_path' => null,
            'status' => UserStatus::ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleEnum::ADMIN->value],
                ['name' => 'Administrador', 'description' => 'Acesso total.']
            )->id,
        ]);
    }

    /**
     * Indicate that the user is a manager.
     */
    public function manager(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleEnum::MANAGER->value],
                ['name' => 'Gerente', 'description' => 'Gestão de projetos.']
            )->id,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => UserStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
