<?php

namespace App\DTOs\Projects;

use App\Enums\ProjectStatus;

readonly class UpdateProjectDTO
{
    public function __construct(
        public ?int $ownerId = null,
        public ?string $name = null,
        public ?string $code = null,
        public ?string $description = null,
        public ?ProjectStatus $status = null,
        public ?string $startDate = null,
        public ?string $dueDate = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            ownerId: isset($data['owner_id']) ? (int) $data['owner_id'] : null,
            name: isset($data['name']) ? trim((string) $data['name']) : null,
            code: isset($data['code']) ? strtoupper(trim((string) $data['code'])) : null,
            description: array_key_exists('description', $data) ? ($data['description'] !== null ? trim((string) $data['description']) : null) : null,
            status: isset($data['status']) ? ProjectStatus::from((string) $data['status']) : null,
            startDate: array_key_exists('start_date', $data) ? $data['start_date'] : null,
            dueDate: array_key_exists('due_date', $data) ? $data['due_date'] : null,
        );
    }
}
