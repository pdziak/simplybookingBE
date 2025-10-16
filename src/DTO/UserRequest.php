<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\Length(min: 3, max: 50)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]+$/', message: 'Login can only contain letters, numbers, and underscores')]
    public ?string $login = null;

    #[Assert\Length(min: 6)]
    public ?string $password = null;

    #[Assert\Length(min: 2, max: 255)]
    public ?string $firstName = null;

    #[Assert\Length(min: 2, max: 255)]
    public ?string $lastName = null;

    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], multiple: true)]
    public array $roles = ['ROLE_USER'];

    #[Assert\Type(type: 'array')]
    public array $assignedAppIds = [];
}
