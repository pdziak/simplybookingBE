<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

class UserResponse
{
    #[Groups(['user:read'])]
    public int $id;

    #[Groups(['user:read'])]
    public string $email;

    #[Groups(['user:read'])]
    public ?string $login;

    #[Groups(['user:read'])]
    public ?string $firstName;

    #[Groups(['user:read'])]
    public ?string $lastName;

    #[Groups(['user:read'])]
    public array $roles;

    #[Groups(['user:read'])]
    public string $createdAt;

    #[Groups(['user:read'])]
    public ?string $updatedAt;

    #[Groups(['user:read'])]
    public array $assignedApps = [];

    public function __construct(
        int $id,
        string $email,
        ?string $login,
        ?string $firstName,
        ?string $lastName,
        array $roles,
        string $createdAt,
        ?string $updatedAt = null,
        array $assignedApps = []
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->login = $login;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->roles = $roles;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->assignedApps = $assignedApps;
    }
}
