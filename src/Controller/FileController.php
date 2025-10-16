<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('', name: 'file_')]
class FileController extends AbstractController
{
    #[Route('/uploads/products/{filename}', name: 'get_product_image', methods: ['GET'])]
    public function getProductImage(string $filename): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/products/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Product image not found');
        }

        $response = new Response();
        $response->headers->set('Content-Type', mime_content_type($filePath));
        $response->setContent(file_get_contents($filePath));
        
        return $response;
    }

    #[Route('/uploads/logos/{filename}', name: 'get_logo', methods: ['GET'])]
    public function getLogo(string $filename): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/logos/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Logo not found');
        }

        $response = new Response();
        $response->headers->set('Content-Type', mime_content_type($filePath));
        $response->setContent(file_get_contents($filePath));
        
        return $response;
    }
}
