<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank]
    public string $login;

    #[Assert\NotBlank]
    public string $password;
}
