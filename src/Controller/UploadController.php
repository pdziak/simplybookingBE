<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/upload', name: 'upload_')]
class UploadController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/logo', name: 'logo', methods: ['POST'])]
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }
        $uploadedFile = $request->files->get('logo');
        
        if (!$uploadedFile) {
            return $this->json(['error' => 'Nie przesłano pliku'], Response::HTTP_BAD_REQUEST);
        }

        // Check if file was uploaded successfully
        if (!$uploadedFile->isValid()) {
            return $this->json(['error' => 'Przesyłanie pliku nie powiodło się: ' . $uploadedFile->getErrorMessage()], Response::HTTP_BAD_REQUEST);
        }

        // Check if file exists and is readable
        if (!$uploadedFile->getPathname() || !file_exists($uploadedFile->getPathname())) {
            return $this->json(['error' => 'Przesłany plik nie jest dostępny'], Response::HTTP_BAD_REQUEST);
        }

        // Get file size safely
        $fileSize = 0;
        if (file_exists($uploadedFile->getPathname())) {
            $fileSize = filesize($uploadedFile->getPathname());
        }

        // Basic validation
        if ($fileSize === 0) {
            return $this->json(['error' => 'Plik jest pusty'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($fileSize > 2 * 1024 * 1024) { // 2MB
            return $this->json(['error' => 'File is too large. Maximum size is 2MB'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check MIME type
        $mimeType = $uploadedFile->getMimeType();
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->json(['error' => 'Nieprawidłowy typ pliku. Dozwolone typy: JPEG, PNG, GIF, WebP'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Generate a unique filename
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        
        // Limit filename length to prevent database issues (max 400 chars for filename)
        // We need to leave room for the uniqid and extension
        if (strlen($safeFilename) > 400) {
            $safeFilename = substr($safeFilename, 0, 400);
        }
        
        $extension = $uploadedFile->guessExtension() ?: 'bin';
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        // Create uploads directory if it doesn't exist
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Move the file to the uploads directory
        try {
            $uploadedFile->move($uploadsDir, $newFilename);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to save file: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Return the URL to the uploaded file
        $fileUrl = 'logos/' . $newFilename;
        
        return $this->json([
            'success' => true,
            'url' => $fileUrl,
            'filename' => $newFilename,
            'originalName' => $uploadedFile->getClientOriginalName(),
            'size' => $fileSize,
            'mimeType' => $mimeType
        ], Response::HTTP_CREATED);
    }

    #[Route('/product-image', name: 'product_image', methods: ['POST'])]
    public function uploadProductImage(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }
        $uploadedFile = $request->files->get('image');
        
        if (!$uploadedFile) {
            return $this->json(['error' => 'Nie przesłano pliku'], Response::HTTP_BAD_REQUEST);
        }

        // Check if file was uploaded successfully
        if (!$uploadedFile->isValid()) {
            return $this->json(['error' => 'Przesyłanie pliku nie powiodło się: ' . $uploadedFile->getErrorMessage()], Response::HTTP_BAD_REQUEST);
        }

        // Check if file exists and is readable
        if (!$uploadedFile->getPathname() || !file_exists($uploadedFile->getPathname())) {
            return $this->json(['error' => 'Przesłany plik nie jest dostępny'], Response::HTTP_BAD_REQUEST);
        }

        // Get file size safely
        $fileSize = 0;
        if (file_exists($uploadedFile->getPathname())) {
            $fileSize = filesize($uploadedFile->getPathname());
        }

        // Basic validation
        if ($fileSize === 0) {
            return $this->json(['error' => 'Plik jest pusty'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($fileSize > 5 * 1024 * 1024) { // 5MB for product images
            return $this->json(['error' => 'Plik jest za duży. Maksymalny rozmiar to 5MB'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check MIME type
        $mimeType = $uploadedFile->getMimeType();
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->json(['error' => 'Nieprawidłowy typ pliku. Dozwolone typy: JPEG, PNG, GIF, WebP'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Generate a unique filename
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        
        // Limit filename length to prevent database issues (max 400 chars for filename)
        // We need to leave room for the uniqid and extension
        if (strlen($safeFilename) > 400) {
            $safeFilename = substr($safeFilename, 0, 400);
        }
        
        $extension = $uploadedFile->guessExtension() ?: 'bin';
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        // Create uploads directory if it doesn't exist
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Move the file to the uploads directory
        try {
            $uploadedFile->move($uploadsDir, $newFilename);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to save file: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Return the URL to the uploaded file
        $fileUrl = 'products/' . $newFilename;
        
        return $this->json([
            'success' => true,
            'url' => $fileUrl,
            'filename' => $newFilename,
            'originalName' => $uploadedFile->getClientOriginalName(),
            'size' => $fileSize,
            'mimeType' => $mimeType
        ], Response::HTTP_CREATED);
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
}
