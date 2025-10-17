<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\App;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SubdomainProductTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;
    private App $testApp;
    private Category $testCategory;
    private Product $testProduct;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        // Create a test user
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $this->entityManager->persist($user);

        // Create a test app
        $this->testApp = new App();
        $this->testApp->setTitle('Test Store');
        $this->testApp->setSlug('test-store');
        $this->testApp->setCompanyName('Test Company');
        $this->testApp->setEmail('store@example.com');
        $this->testApp->setDescription('A test store');
        $this->testApp->setLogo('logos/test-logo.png');
        $this->testApp->setOwner($user);
        $this->entityManager->persist($this->testApp);

        // Create a test category
        $this->testCategory = new Category();
        $this->testCategory->setCategoryName('Test Category');
        $this->testCategory->setApp($this->testApp);
        $this->entityManager->persist($this->testCategory);

        // Create a test product
        $this->testProduct = new Product();
        $this->testProduct->setProductName('Test Product');
        $this->testProduct->setProductDescription('A test product description');
        $this->testProduct->setProductPrice(29.99);
        $this->testProduct->setProductStock(10);
        $this->testProduct->setProductSku('TEST-001');
        $this->testProduct->setCategory($this->testCategory);
        $this->entityManager->persist($this->testProduct);

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->remove($this->testProduct);
        $this->entityManager->remove($this->testCategory);
        $this->entityManager->remove($this->testApp);
        $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->delete()
            ->where('u.email = :email')
            ->setParameter('email', 'test@example.com')
            ->getQuery()
            ->execute();
        $this->entityManager->flush();
    }

    public function testGetProductsBySubdomain(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/test-store/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = $response->toArray();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test Product', $data[0]['productName']);
    }

    public function testGetProductsByNonExistentSubdomain(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/non-existent/products');

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['error' => 'Subdomain not found']);
    }

    public function testGetProductById(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/test-store/products/' . $this->testProduct->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = $response->toArray();
        $this->assertEquals('Test Product', $data['productName']);
        $this->assertEquals(29.99, $data['productPrice']);
        $this->assertEquals(10, $data['productStock']);
    }

    public function testGetProductByIdFromWrongSubdomain(): void
    {
        // Create another app with different slug
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $anotherApp = new App();
        $anotherApp->setTitle('Another Store');
        $anotherApp->setSlug('another-store');
        $anotherApp->setCompanyName('Another Company');
        $anotherApp->setEmail('another@example.com');
        $anotherApp->setLogo('logos/another-logo.png');
        $anotherApp->setOwner($user);
        $this->entityManager->persist($anotherApp);
        $this->entityManager->flush();

        $response = static::createClient()->request('GET', '/subdomain/another-store/products/' . $this->testProduct->getId());

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['error' => 'Product not found']);

        // Clean up
        $this->entityManager->remove($anotherApp);
        $this->entityManager->flush();
    }

    public function testGetProductsByCategory(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/test-store/products/category/' . $this->testCategory->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = $response->toArray();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test Product', $data[0]['productName']);
    }

    public function testSearchProducts(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/test-store/products/search?q=Test');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = $response->toArray();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test Product', $data[0]['productName']);
    }

    public function testSearchProductsByPriceRange(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/test-store/products/search?min_price=20&max_price=50');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = $response->toArray();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test Product', $data[0]['productName']);
    }

    public function testSearchProductsInStock(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/test-store/products/search?in_stock=true');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = $response->toArray();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test Product', $data[0]['productName']);
    }

    public function testSearchProductsWithoutParameters(): void
    {
        $response = static::createClient()->request('GET', '/subdomain/test-store/products/search');

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['error' => 'At least one search parameter is required']);
    }
}
