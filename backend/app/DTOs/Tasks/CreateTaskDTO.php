<?php

namespace App\DTOs\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;

readonly class CreateTaskDTO
{
    public function __construct(
        public int $projectId,
        public int $createdBy,
        public string $title,
        public ?string $description = null,
        public TaskStatus $status = TaskStatus::TODO,
        public TaskPriority $priority = TaskPriority::MEDIUM,
        public ?int $assignedTo = null,
        public ?string $dueDate = null,
        public ?float $estimatedHours = null,
        public int $order = 0,
    ) {}

    public static function fromRequest(array $data, int $projectId, int $createdBy): self
    {
        return new self(
            projectId: $projectId,
            createdBy: $createdBy,
            title: trim((string) $data['title']),
            description: isset($data['description']) ? trim((string) $data['description']) : null,
            status: isset($data['status']) ? TaskStatus::from((string) $data['status']) : TaskStatus::TODO,
            priority: isset($data['priority']) ? TaskPriority::from((string) $data['priority']) : TaskPriority::MEDIUM,
            assignedTo: ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            dueDate: ! empty($data['due_date']) ? (string) $data['due_date'] : null,
            estimatedHours: isset($data['estimated_hours']) && $data['estimated_hours'] !== '' ? (float) $data['estimated_hours'] : null,
            order: isset($data['order']) ? (int) $data['order'] : 0,
        );
    }
}
