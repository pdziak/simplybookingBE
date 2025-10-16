<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class AppTest extends ApiTestCase
{
    public function testCreateApp(): void
    {
        static::createClient()->request('POST', '/api/apps', [
            'json' => [
                'title' => 'Test App',
                'slug' => 'test-app',
                'companyName' => 'Test Company',
                'email' => 'test@example.com',
                'description' => 'This is a test app description',
                'logo' => 'https://example.com/logo.png',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/App',
            '@type' => 'App',
            'title' => 'Test App',
            'slug' => 'test-app',
            'companyName' => 'Test Company',
            'email' => 'test@example.com',
            'description' => 'This is a test app description',
            'logo' => 'https://example.com/logo.png',
        ]);
    }

    public function testCreateAppWithoutOptionalFields(): void
    {
        static::createClient()->request('POST', '/api/apps', [
            'json' => [
                'title' => 'Minimal App',
                'slug' => 'minimal-app',
                'companyName' => 'Minimal Company',
                'email' => 'minimal@example.com',
                'logo' => 'https://example.com/minimal-logo.png',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/App',
            '@type' => 'App',
            'title' => 'Minimal App',
            'slug' => 'minimal-app',
            'companyName' => 'Minimal Company',
            'email' => 'minimal@example.com',
            'logo' => 'https://example.com/minimal-logo.png',
        ]);
    }

    public function testCreateAppWithInvalidEmail(): void
    {
        static::createClient()->request('POST', '/api/apps', [
            'json' => [
                'title' => 'Invalid App',
                'slug' => 'invalid-app',
                'companyName' => 'Invalid Company',
                'email' => 'invalid-email',
                'logo' => 'https://example.com/logo.png',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateAppWithInvalidSlug(): void
    {
        static::createClient()->request('POST', '/api/apps', [
            'json' => [
                'title' => 'Invalid App',
                'slug' => 'Invalid Slug!',
                'companyName' => 'Invalid Company',
                'email' => 'test@example.com',
                'logo' => 'https://example.com/logo.png',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateAppWithMissingRequiredFields(): void
    {
        static::createClient()->request('POST', '/api/apps', [
            'json' => [
                'title' => 'Incomplete App',
                'slug' => 'incomplete-app',
                // Missing companyName, email, and logo
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetAppsCollection(): void
    {
        static::createClient()->request('GET', '/api/apps');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }
}
