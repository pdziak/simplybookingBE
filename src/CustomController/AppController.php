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

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $apps = $this->entityManager->getRepository(App::class)->findAll();
        
        // Serialize with context to avoid circular references
        $jsonData = $this->serializer->serialize($apps, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        
        $response = new JsonResponse($jsonData, Response::HTTP_OK, [], true);
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }

    #[Route('', name: 'create', methods: ['POST', 'OPTIONS'])]
    public function create(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            return $response;
        }

        $data = json_decode($request->getContent(), true);
        
        $app = new App();
        $app->setTitle($data['title'] ?? '');
        $app->setSlug($data['slug'] ?? '');
        $app->setCompanyName($data['companyName'] ?? '');
        $app->setEmail($data['email'] ?? '');
        $app->setDescription($data['description'] ?? '');
        $app->setLogo($data['logo'] ?? '');
        
        $this->entityManager->persist($app);
        $this->entityManager->flush();
        
        $jsonData = $this->serializer->serialize($app, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        $response = new JsonResponse($jsonData, Response::HTTP_CREATED, [], true);
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
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
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'OPTIONS'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
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
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE', 'OPTIONS'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            return $response;
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($app);
        $this->entityManager->flush();
        
        $response = new JsonResponse(['message' => 'App deleted successfully'], Response::HTTP_OK);
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }
}