<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class CustomAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $event = new AuthenticationFailureEvent($exception, new JWTAuthenticationFailureResponse());
        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_FAILURE);

        $response = $event->getResponse();
        
        // Check if the exception is related to email verification
        if ($exception instanceof EmailNotVerifiedException) {
            $response->setData([
                'code' => 403,
                'message' => 'Please confirm your email address'
            ]);
        } else {
            $response->setData([
                'code' => 401,
                'message' => 'Invalid credentials'
            ]);
        }

        return $response;
    }
}
