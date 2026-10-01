<?php

namespace App\DTOs\Projects;

use App\Enums\ProjectRole;

readonly class AssignMemberDTO
{
    public function __construct(
        public int $userId,
        public ProjectRole $role = ProjectRole::MEMBER,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            role: isset($data['role']) ? ProjectRole::from((string) $data['role']) : ProjectRole::MEMBER,
        );
    }
}
