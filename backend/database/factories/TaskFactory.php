<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'due_date' => now()->addDays(fake()->numberBetween(1, 15))->format('Y-m-d'),
            'estimated_hours' => fake()->randomFloat(2, 1, 40),
            'order' => 0,
            'completed_at' => null,
        ];
    }
}
