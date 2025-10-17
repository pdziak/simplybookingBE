<?php

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTPayloadHandler
{
    public function onLexikJwtAuthenticationOnJwtCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        $payload = $event->getData();
        
        // Always include both email and login in the JWT payload
        // This ensures the JWT authenticator can find the user regardless of which field is used
        $payload['email'] = $user->getEmail();
        $payload['login'] = $user->getLogin();
        
        // Use email as the primary identifier since it's always present and unique
        $payload['username'] = $user->getEmail();
        
        $event->setData($payload);
    }
}
