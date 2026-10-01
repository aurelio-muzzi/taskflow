<?php

namespace App\DTOs\Auth;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: strtolower(trim((string) ($data['email'] ?? ''))),
            password: (string) ($data['password'] ?? ''),
            deviceName: isset($data['device_name']) ? (string) $data['device_name'] : 'taskflow-web',
        );
    }
}
