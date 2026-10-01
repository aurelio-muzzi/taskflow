<?php

namespace App\DTOs\Comments;

readonly class CreateTaskCommentDTO
{
    public function __construct(
        public int $taskId,
        public int $userId,
        public string $content,
    ) {}

    public static function fromRequest(array $data, int $taskId, int $userId): self
    {
        return new self(
            taskId: $taskId,
            userId: $userId,
            content: trim((string) $data['content']),
        );
    }
}
