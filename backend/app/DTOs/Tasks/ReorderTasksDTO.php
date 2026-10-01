<?php

namespace App\DTOs\Tasks;

use App\Enums\TaskStatus;

readonly class ReorderItemDTO
{
    public function __construct(
        public int $id,
        public int $order,
        public ?TaskStatus $status = null,
    ) {}
}

readonly class ReorderTasksDTO
{
    /**
     * @param  array<ReorderItemDTO>  $items
     */
    public function __construct(
        public array $items,
    ) {}

    public static function fromRequest(array $data): self
    {
        $items = array_map(function ($item) {
            return new ReorderItemDTO(
                id: (int) $item['id'],
                order: (int) $item['order'],
                status: isset($item['status']) && $item['status'] !== null ? TaskStatus::from((string) $item['status']) : null,
            );
        }, $data['tasks'] ?? []);

        return new self(items: $items);
    }
}
