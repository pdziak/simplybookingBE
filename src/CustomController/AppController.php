<?php

namespace App\CustomController;

use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/apps', name: 'custom_app_')]
class AppController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'list_all', methods: ['GET'])]
    public function listAll(): JsonResponse
    {
        // Get all apps (for admin or public access)
        $apps = $this->entityManager->getRepository(App::class)->findAll();
        
        // Serialize with context to avoid circular references
        $jsonData = $this->serializer->serialize($apps, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        
        $response = new JsonResponse($jsonData, Response::HTTP_OK, [], true);
        
        return $response;
    }

    #[Route('/mine', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Get only apps belonging to the current user
        $apps = $this->entityManager->getRepository(App::class)
            ->findBy(['owner' => $user]);
        
        // Serialize with context to avoid circular references
        $jsonData = $this->serializer->serialize($apps, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        
        $response = new JsonResponse($jsonData, Response::HTTP_OK, [], true);
        
        return $response;
    }

    #[Route('', name: 'create', methods: ['POST', 'OPTIONS'])]
    public function create(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            return $response;
        }

        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        // Check if user already has an app with this slug
        $existingApp = $this->entityManager->getRepository(App::class)
            ->findOneBy(['slug' => $data['slug'] ?? '', 'owner' => $user]);
        
        if ($existingApp) {
            // Update existing app
            $existingApp->setTitle($data['title'] ?? '');
            $existingApp->setCompanyName($data['companyName'] ?? '');
            $existingApp->setEmail($data['email'] ?? '');
            $existingApp->setDescription($data['description'] ?? '');
            $existingApp->setLogo($data['logo'] ?? '');
            $existingApp->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->flush();
            $app = $existingApp;
        } else {
            // Check if slug is already taken by another user
            $slugExists = $this->entityManager->getRepository(App::class)
                ->findOneBy(['slug' => $data['slug'] ?? '']);
            
            if ($slugExists) {
                return $this->json(['error' => 'This URL slug is already taken. Please choose a different one.'], Response::HTTP_CONFLICT);
            }
            
            // Create new app
            $app = new App();
            $app->setTitle($data['title'] ?? '');
            $app->setSlug($data['slug'] ?? '');
            $app->setCompanyName($data['companyName'] ?? '');
            $app->setEmail($data['email'] ?? '');
            $app->setDescription($data['description'] ?? '');
            $app->setLogo($data['logo'] ?? '');
            $app->setOwner($user); // Set the owner (user) for the app
            
            $this->entityManager->persist($app);
            $this->entityManager->flush();
        }
        
        $jsonData = $this->serializer->serialize($app, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        $response = new JsonResponse($jsonData, Response::HTTP_CREATED, [], true);
        
        return $response;
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $app = $this->entityManager->getRepository(App::class)->find($id);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }
        
        $jsonData = $this->serializer->serialize($app, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        $response = new JsonResponse($jsonData, Response::HTTP_OK, [], true);
        
        return $response;
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'OPTIONS'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            return $response;
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['title'])) {
            $app->setTitle($data['title']);
        }
        if (isset($data['slug'])) {
            $app->setSlug($data['slug']);
        }
        if (isset($data['companyName'])) {
            $app->setCompanyName($data['companyName']);
        }
        if (isset($data['email'])) {
            $app->setEmail($data['email']);
        }
        if (isset($data['description'])) {
            $app->setDescription($data['description']);
        }
        if (isset($data['logo'])) {
            $app->setLogo($data['logo']);
        }
        
        $this->entityManager->flush();
        
        $jsonData = $this->serializer->serialize($app, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        $response = new JsonResponse($jsonData, Response::HTTP_OK, [], true);
        
        return $response;
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE', 'OPTIONS'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            return $response;
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($app);
        $this->entityManager->flush();
        
        $response = new JsonResponse(['message' => 'App deleted successfully'], Response::HTTP_OK);
        
        return $response;
    }
}