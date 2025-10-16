<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class EmailNotVerifiedException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Please confirm your email address';
    }
}
