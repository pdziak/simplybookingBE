<?php

namespace App\ProductsController;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Get all products that belong to categories from apps owned by the current user
        $products = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('c.app', 'a')
            ->where('a.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
        
        return $this->json($products, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setProductName($data['productName'] ?? '');
        $product->setProductDescription($data['productDescription'] ?? null);
        $product->setProductImage($data['productImage'] ?? null);
        $product->setProductPrice((float) ($data['productPrice'] ?? 0));
        $product->setProductStock((int) ($data['productStock'] ?? 0));
        $product->setProductSku($data['productSku'] ?? null);

        // If category_id is provided, find and set the category
        if (isset($data['category_id'])) {
            $category = $this->entityManager->getRepository(Category::class)->find($data['category_id']);
            if (!$category) {
                return $this->json(['error' => 'Category not found'], Response::HTTP_BAD_REQUEST);
            }
            
            // Check if the category belongs to an app owned by the current user
            if ($category->getApp()->getOwner() !== $user) {
                return $this->json(['error' => 'Access denied. You can only create products for categories from your own apps.'], Response::HTTP_FORBIDDEN);
            }
            
            $product->setCategory($category);
        } else {
            return $this->json(['error' => 'category_id is required'], Response::HTTP_BAD_REQUEST);
        }

        // Validate the product
        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return $this->json($product, Response::HTTP_CREATED, [], ['groups' => ['product:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create product', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $product = $this->entityManager->getRepository(Product::class)->find($id);
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the product belongs to a category from an app owned by the current user
        if ($product->getCategory()->getApp()->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied. You can only view products from your own apps.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($product, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $product = $this->entityManager->getRepository(Product::class)->find($id);
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the product belongs to a category from an app owned by the current user
        if ($product->getCategory()->getApp()->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied. You can only update products from your own apps.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['productName'])) {
            $product->setProductName($data['productName']);
        }

        if (isset($data['productDescription'])) {
            $product->setProductDescription($data['productDescription']);
        }

        if (isset($data['productImage'])) {
            $product->setProductImage($data['productImage']);
        }

        if (isset($data['productPrice'])) {
            $product->setProductPrice((float) $data['productPrice']);
        }

        if (isset($data['productStock'])) {
            $product->setProductStock((int) $data['productStock']);
        }

        if (isset($data['productSku'])) {
            $product->setProductSku($data['productSku']);
        }

        if (isset($data['category_id'])) {
            $category = $this->entityManager->getRepository(Category::class)->find($data['category_id']);
            if (!$category) {
                return $this->json(['error' => 'Category not found'], Response::HTTP_BAD_REQUEST);
            }
            
            // Check if the category belongs to an app owned by the current user
            if ($category->getApp()->getOwner() !== $user) {
                return $this->json(['error' => 'Access denied. You can only assign products to categories from your own apps.'], Response::HTTP_FORBIDDEN);
            }
            
            $product->setCategory($category);
        }

        $product->setUpdatedAt(new \DateTimeImmutable());

        // Validate the product
        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->flush();

            return $this->json($product, Response::HTTP_OK, [], ['groups' => ['product:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update product', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $product = $this->entityManager->getRepository(Product::class)->find($id);
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the product belongs to a category from an app owned by the current user
        if ($product->getCategory()->getApp()->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied. You can only delete products from your own apps.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->remove($product);
            $this->entityManager->flush();

            return $this->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete product', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
