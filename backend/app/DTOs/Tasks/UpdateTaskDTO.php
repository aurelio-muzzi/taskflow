<?php

namespace App\DTOs\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;

readonly class UpdateTaskDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?TaskStatus $status = null,
        public ?TaskPriority $priority = null,
        public ?int $assignedTo = null,
        public bool $clearAssignedTo = false,
        public ?string $dueDate = null,
        public ?float $estimatedHours = null,
        public ?int $order = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            title: isset($data['title']) ? trim((string) $data['title']) : null,
            description: array_key_exists('description', $data) ? ($data['description'] !== null ? trim((string) $data['description']) : null) : null,
            status: isset($data['status']) ? TaskStatus::from((string) $data['status']) : null,
            priority: isset($data['priority']) ? TaskPriority::from((string) $data['priority']) : null,
            assignedTo: ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            clearAssignedTo: array_key_exists('assigned_to', $data) && empty($data['assigned_to']),
            dueDate: array_key_exists('due_date', $data) ? (! empty($data['due_date']) ? (string) $data['due_date'] : null) : null,
            estimatedHours: array_key_exists('estimated_hours', $data) ? ($data['estimated_hours'] !== null && $data['estimated_hours'] !== '' ? (float) $data['estimated_hours'] : null) : null,
            order: isset($data['order']) ? (int) $data['order'] : null,
        );
    }
}
