<?php

namespace App\DTOs\Tasks;

use App\Enums\TaskStatus;

readonly class UpdateTaskStatusDTO
{
    public function __construct(
        public TaskStatus $status,
        public ?int $order = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            status: TaskStatus::from((string) $data['status']),
            order: isset($data['order']) ? (int) $data['order'] : null,
        );
    }
}
