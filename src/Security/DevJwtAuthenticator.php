<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class DevJwtAuthenticator extends JWTAuthenticator
{
    public function authenticate(Request $request): Passport
    {
        // In development mode, allow requests without JWT token
        if ($_ENV['APP_ENV'] === 'dev') {
            // Create a mock passport for development
            return new \Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport(
                new \Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge('dev-user', function() {
                    return new class {
                        public function getUserIdentifier(): string { return 'dev-user'; }
                        public function getRoles(): array { return ['ROLE_USER', 'ROLE_ADMIN']; }
                        public function getPassword(): ?string { return null; }
                        public function getSalt(): ?string { return null; }
                        public function eraseCredentials(): void {}
                    };
                })
            );
        }

        // In production, use the parent JWT authenticator
        return parent::authenticate($request);
    }

    public function supports(Request $request): ?bool
    {
        // Always support requests in development mode
        if ($_ENV['APP_ENV'] === 'dev') {
            return true;
        }

        // In production, use parent logic
        return parent::supports($request);
    }
}
