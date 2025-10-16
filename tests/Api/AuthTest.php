<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testRegisterUser(): void
    {
        $response = static::createClient()->request('POST', '/api/auth/register', [
            'json' => [
                'email' => 'test@example.com',
                'password' => 'password123'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'user' => [
                'email' => 'test@example.com',
                'firstName' => null,
                'lastName' => null
            ]
        ]);
        $this->assertArrayHasKey('token', $response->toArray());
    }

    public function testLoginUser(): void
    {
        // First create a user
        $user = new User();
        $user->setEmail('login@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Then try to login
        $response = static::createClient()->request('POST', '/api/auth/login', [
            'json' => [
                'email' => 'login@example.com',
                'password' => 'password123'
            ]
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'user' => [
                'email' => 'login@example.com',
                'firstName' => null,
                'lastName' => null
            ]
        ]);
        $this->assertArrayHasKey('token', $response->toArray());
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $response = static::createClient()->request('POST', '/api/auth/login', [
            'json' => [
                'email' => 'nonexistent@example.com',
                'password' => 'wrongpassword'
            ]
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'error' => 'Invalid credentials'
        ]);
    }

    public function testGetCurrentUser(): void
    {
        // Create and login a user
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login to get token
        $loginResponse = static::createClient()->request('POST', '/api/auth/login', [
            'json' => [
                'email' => 'me@example.com',
                'password' => 'password123'
            ]
        ]);

        $token = $loginResponse->toArray()['token'];

        // Use token to get current user
        $response = static::createClient()->request('GET', '/api/auth/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'email' => 'me@example.com',
            'firstName' => null,
            'lastName' => null
        ]);
    }
}
