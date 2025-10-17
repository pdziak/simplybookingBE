<?php

namespace App\CustomController;

use App\Entity\Category;
use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/categories', name: 'api_categories_')]
class CategoryController extends AbstractController
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

        // Get all categories that belong to apps owned by the current user
        $categories = $this->entityManager->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->join('c.app', 'a')
            ->where('a.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
        
        return $this->json($categories, Response::HTTP_OK, [], ['groups' => ['category:read']]);
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

        $category = new Category();
        $category->setCategoryName($data['categoryName'] ?? '');

        // If app_id is provided, find and set the app
        if (isset($data['app_id'])) {
            $app = $this->entityManager->getRepository(App::class)->find($data['app_id']);
            if (!$app) {
                return $this->json(['error' => 'App not found'], Response::HTTP_BAD_REQUEST);
            }
            
            // Check if the app belongs to the current user
            if ($app->getOwner() !== $user) {
                return $this->json(['error' => 'Access denied. You can only create categories for your own apps.'], Response::HTTP_FORBIDDEN);
            }
            
            $category->setApp($app);
        } else {
            return $this->json(['error' => 'app_id is required'], Response::HTTP_BAD_REQUEST);
        }

        // Validate the category
        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            return $this->json($category, Response::HTTP_CREATED, [], ['groups' => ['category:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create category', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $category = $this->entityManager->getRepository(Category::class)->find($id);
        
        if (!$category) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the category belongs to an app owned by the current user
        if ($category->getApp()->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied. You can only view categories from your own apps.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($category, Response::HTTP_OK, [], ['groups' => ['category:read']]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $category = $this->entityManager->getRepository(Category::class)->find($id);
        
        if (!$category) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the category belongs to an app owned by the current user
        if ($category->getApp()->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied. You can only update categories from your own apps.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['categoryName'])) {
            $category->setCategoryName($data['categoryName']);
        }

        if (isset($data['app_id'])) {
            $app = $this->entityManager->getRepository(App::class)->find($data['app_id']);
            if (!$app) {
                return $this->json(['error' => 'App not found'], Response::HTTP_BAD_REQUEST);
            }
            
            // Check if the app belongs to the current user
            if ($app->getOwner() !== $user) {
                return $this->json(['error' => 'Access denied. You can only assign categories to your own apps.'], Response::HTTP_FORBIDDEN);
            }
            
            $category->setApp($app);
        }

        $category->setUpdatedAt(new \DateTimeImmutable());

        // Validate the category
        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->flush();

            return $this->json($category, Response::HTTP_OK, [], ['groups' => ['category:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update category', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $category = $this->entityManager->getRepository(Category::class)->find($id);
        
        if (!$category) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the category belongs to an app owned by the current user
        if ($category->getApp()->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied. You can only delete categories from your own apps.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            return $this->json(['message' => 'Category deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete category', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
