<?php

namespace App\DTOs\Users;

use App\Enums\UserStatus;

readonly class CreateUserDTO
{
    public function __construct(
        public int $roleId,
        public string $name,
        public string $email,
        public string $password,
        public UserStatus $status = UserStatus::ACTIVE,
        public ?string $avatarPath = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            roleId: (int) $data['role_id'],
            name: trim((string) $data['name']),
            email: strtolower(trim((string) $data['email'])),
            password: (string) $data['password'],
            status: isset($data['status']) ? UserStatus::from((string) $data['status']) : UserStatus::ACTIVE,
            avatarPath: isset($data['avatar_path']) ? (string) $data['avatar_path'] : null,
        );
    }
}
