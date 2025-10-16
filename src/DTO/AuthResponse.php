<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

class AuthResponse
{
    #[Groups(['auth:read'])]
    public string $token;

    #[Groups(['auth:read'])]
    public string $refreshToken;

    #[Groups(['auth:read'])]
    public array $user;

    public function __construct(string $token, string $refreshToken, array $user)
    {
        $this->token = $token;
        $this->refreshToken = $refreshToken;
        $this->user = $user;
    }
}
