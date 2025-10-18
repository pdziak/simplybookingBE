<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LoginRequest
{
    // Email field - optional if login is provided
    public ?string $email = null;

    #[Assert\NotBlank]
    public string $password;

    // Optional login field - if provided, it will be used instead of email
    public ?string $login = null;

    // Custom validation to ensure either email or login is provided
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (empty($this->email) && empty($this->login)) {
            $context->buildViolation('Either email or login must be provided.')
                ->atPath('email')
                ->addViolation();
        }
    }
}
