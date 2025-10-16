<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EmailVerifiedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Check if user's email is verified
        if ($user->getEmailVerifiedAt() === null) {
            throw new CustomUserMessageAccountStatusException(
                'Please verify your email address before logging in. Check your email for verification instructions.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No post-authentication checks needed
    }
}
