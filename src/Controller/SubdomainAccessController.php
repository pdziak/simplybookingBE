<?php

namespace App\Controller;

use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('', name: 'subdomain_access_')]
class SubdomainAccessController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    #[Route('/check-access/{slug}', name: 'check_access', methods: ['GET', 'OPTIONS'])]
    public function checkAccess(string $slug, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            return $response;
        }

        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Find app by slug
        $app = $this->entityManager->getRepository(App::class)
            ->findOneBy(['slug' => $slug]);
        
        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user has access to this app
        // User has access if:
        // 1. User is the owner of the app, OR
        // 2. User is assigned to the app (in user_apps table)
        $hasAccess = false;
        
        // Check if user is the owner
        if ($app->getOwner()->getId() === $user->getId()) {
            $hasAccess = true;
        }
        
        // Check if user is assigned to the app
        if (!$hasAccess) {
            $hasAccess = $app->getAssignedUsers()->contains($user);
        }

        if (!$hasAccess) {
            return $this->json([
                'error' => 'Access denied',
                'message' => 'You do not have permission to access this subdomain'
            ], Response::HTTP_FORBIDDEN);
        }

        // Return app data if user has access
        $jsonData = $this->serializer->serialize($app, 'json', [
            'groups' => ['app:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        $response = new JsonResponse($jsonData, Response::HTTP_OK, [], true);
        
        return $response;
    }
}
