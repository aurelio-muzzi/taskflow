<?php

namespace App\DTOs\Projects;

use App\Enums\ProjectStatus;

readonly class CreateProjectDTO
{
    public function __construct(
        public int $ownerId,
        public string $name,
        public string $code,
        public ?string $description = null,
        public ProjectStatus $status = ProjectStatus::PLANNING,
        public ?string $startDate = null,
        public ?string $dueDate = null,
        /** @var array<array{user_id: int, role: string}> */
        public array $members = [],
    ) {}

    public static function fromRequest(array $data, int $defaultOwnerId): self
    {
        return new self(
            ownerId: isset($data['owner_id']) ? (int) $data['owner_id'] : $defaultOwnerId,
            name: trim((string) $data['name']),
            code: strtoupper(trim((string) $data['code'])),
            description: isset($data['description']) ? trim((string) $data['description']) : null,
            status: isset($data['status']) ? ProjectStatus::from((string) $data['status']) : ProjectStatus::PLANNING,
            startDate: ! empty($data['start_date']) ? (string) $data['start_date'] : null,
            dueDate: ! empty($data['due_date']) ? (string) $data['due_date'] : null,
            members: $data['members'] ?? [],
        );
    }
}
