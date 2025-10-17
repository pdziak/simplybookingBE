<?php

namespace App\ProductsController;

use App\Entity\Product;
use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/subdomain', name: 'api_subdomain_products_')]
class SubdomainProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/{slug}/products', name: 'list', methods: ['GET'])]
    public function list(string $slug): JsonResponse
    {
        // Find app by slug
        $app = $this->entityManager->getRepository(App::class)
            ->findOneBy(['slug' => $slug]);
        
        if (!$app) {
            return $this->json(['error' => 'Subdomain not found'], Response::HTTP_NOT_FOUND);
        }

        // Get all products that belong to categories from this app
        $products = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('c.app', 'a')
            ->where('a.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->json($products, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }

    #[Route('/{slug}/products/{id}', name: 'show', methods: ['GET'])]
    public function show(string $slug, int $id): JsonResponse
    {
        // Find app by slug
        $app = $this->entityManager->getRepository(App::class)
            ->findOneBy(['slug' => $slug]);
        
        if (!$app) {
            return $this->json(['error' => 'Subdomain not found'], Response::HTTP_NOT_FOUND);
        }

        $product = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('c.app', 'a')
            ->where('p.id = :id')
            ->andWhere('a.slug = :slug')
            ->setParameter('id', $id)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($product, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }

    #[Route('/{slug}/products/category/{categoryId}', name: 'list_by_category', methods: ['GET'])]
    public function listByCategory(string $slug, int $categoryId): JsonResponse
    {
        // Find app by slug
        $app = $this->entityManager->getRepository(App::class)
            ->findOneBy(['slug' => $slug]);
        
        if (!$app) {
            return $this->json(['error' => 'Subdomain not found'], Response::HTTP_NOT_FOUND);
        }

        // Get all products that belong to the specific category in this app
        $products = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('c.app', 'a')
            ->where('a.slug = :slug')
            ->andWhere('c.id = :categoryId')
            ->setParameter('slug', $slug)
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->json($products, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }

    #[Route('/{slug}/products/search', name: 'search', methods: ['GET'])]
    public function search(string $slug, Request $request): JsonResponse
    {
        // Find app by slug
        $app = $this->entityManager->getRepository(App::class)
            ->findOneBy(['slug' => $slug]);
        
        if (!$app) {
            return $this->json(['error' => 'Subdomain not found'], Response::HTTP_NOT_FOUND);
        }

        $query = $request->query->get('q', '');
        $categoryId = $request->query->get('category_id');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $inStock = $request->query->get('in_stock');

        if (empty($query) && !$categoryId && !$minPrice && !$maxPrice && !$inStock) {
            return $this->json(['error' => 'At least one search parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $qb = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('c.app', 'a')
            ->where('a.slug = :slug')
            ->setParameter('slug', $slug);

        if (!empty($query)) {
            $qb->andWhere('(p.productName LIKE :query OR p.productDescription LIKE :query)')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.productPrice >= :minPrice')
               ->setParameter('minPrice', (float) $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.productPrice <= :maxPrice')
               ->setParameter('maxPrice', (float) $maxPrice);
        }

        if ($inStock === 'true' || $inStock === '1') {
            $qb->andWhere('p.productStock > 0');
        }

        $products = $qb->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->json($products, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }
}
