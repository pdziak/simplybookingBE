<?php

namespace App\Controller;

use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dev', name: 'dev_')]
class DevController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/apps', name: 'apps', methods: ['GET'])]
    public function getApps(Request $request): JsonResponse
    {
        // Only allow in development mode
        if ($_ENV['APP_ENV'] !== 'dev') {
            return $this->json(['error' => 'Development endpoint not available'], 403);
        }

        $apps = $this->entityManager->getRepository(App::class)->findAll();

        $data = array_map(function (App $app) {
            return [
                'id' => $app->getId(),
                'title' => $app->getTitle(),
                'slug' => $app->getSlug(),
                'companyName' => $app->getCompanyName(),
                'email' => $app->getEmail(),
                'description' => $app->getDescription(),
                'logo' => $app->getLogo(),
                'createdAt' => $app->getCreatedAt()?->format('c'),
                'updatedAt' => $app->getUpdatedAt()?->format('c'),
            ];
        }, $apps);

        $response = $this->json([
            'data' => $data,
            'total' => count($data)
        ]);

        // Add CORS headers
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }

    #[Route('/apps', name: 'apps_options', methods: ['OPTIONS'])]
    public function appsOptions(): JsonResponse
    {
        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '3600');
        return $response;
    }
}
