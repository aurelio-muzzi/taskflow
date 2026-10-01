<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Project';
        $code = strtoupper(Str::slug(fake()->unique()->lexify('PROJ-???')));

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'code' => $code,
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ];
    }
}
