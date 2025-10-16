<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\Length(min: 3, max: 50)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]+$/', message: 'Login can only contain letters, numbers, and underscores')]
    public ?string $login = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;
}
