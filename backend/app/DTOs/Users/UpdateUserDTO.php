<?php

namespace App\DTOs\Users;

use App\Enums\UserStatus;

readonly class UpdateUserDTO
{
    public function __construct(
        public ?int $roleId = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?UserStatus $status = null,
        public ?string $avatarPath = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            roleId: isset($data['role_id']) ? (int) $data['role_id'] : null,
            name: isset($data['name']) ? trim((string) $data['name']) : null,
            email: isset($data['email']) ? strtolower(trim((string) $data['email'])) : null,
            password: ! empty($data['password']) ? (string) $data['password'] : null,
            status: isset($data['status']) ? UserStatus::from((string) $data['status']) : null,
            avatarPath: array_key_exists('avatar_path', $data) ? $data['avatar_path'] : null,
        );
    }
}
