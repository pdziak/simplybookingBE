<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/health', name: 'api_health_')]
class HealthController extends AbstractController
{
    #[Route('', name: 'check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'message' => 'API is healthy',
            'timestamp' => date('c')
        ], Response::HTTP_OK);
    }
}
