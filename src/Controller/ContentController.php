<?php

namespace App\Controller;

use App\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/content', name: 'content_')]
class ContentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    // Public endpoints (no authentication required)
    #[Route('/public', name: 'public_list', methods: ['GET'])]
    public function publicList(): JsonResponse
    {
        // Get only active content for public access
        $content = $this->entityManager->getRepository(Content::class)
            ->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
    }

    #[Route('/public/slug/{slug}', name: 'public_show_by_slug', methods: ['GET'])]
    public function publicShowBySlug(string $slug): JsonResponse
    {
        $content = $this->entityManager->getRepository(Content::class)
            ->findOneBy(['slug' => $slug]);
        
        if (!$content) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        // Only return if content is active
        if (!$content->isActive()) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
    }

    #[Route('/public/{id}', name: 'public_show', methods: ['GET'])]
    public function publicShow(int $id): JsonResponse
    {
        $content = $this->entityManager->getRepository(Content::class)->find($id);
        
        if (!$content) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        // Only return if content is active
        if (!$content->isActive()) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
    }

    // Authenticated endpoints (require authentication)
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Get all content
        $content = $this->entityManager->getRepository(Content::class)
            ->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
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

        $content = new Content();
        $content->setTitle($data['title'] ?? '');
        $content->setSlug($data['slug'] ?? '');
        $content->setDescription($data['description'] ?? null);
        $content->setIsActive($data['isActive'] ?? true);

        // Validate the content
        $errors = $this->validator->validate($content);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($content);
            $this->entityManager->flush();

            return $this->json($content, Response::HTTP_CREATED, [], ['groups' => ['content:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create content', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/slug/{slug}', name: 'show_by_slug', methods: ['GET'])]
    public function showBySlug(string $slug): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $content = $this->entityManager->getRepository(Content::class)
            ->findOneBy(['slug' => $slug]);
        
        if (!$content) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $content = $this->entityManager->getRepository(Content::class)->find($id);
        
        if (!$content) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $content = $this->entityManager->getRepository(Content::class)->find($id);
        
        if (!$content) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) {
            $content->setTitle($data['title']);
        }

        if (isset($data['slug'])) {
            $content->setSlug($data['slug']);
        }

        if (isset($data['description'])) {
            $content->setDescription($data['description']);
        }

        if (isset($data['isActive'])) {
            $content->setIsActive($data['isActive']);
        }

        $content->setUpdatedAt(new \DateTimeImmutable());

        // Validate the content
        $errors = $this->validator->validate($content);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->flush();

            return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update content', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $content = $this->entityManager->getRepository(Content::class)->find($id);
        
        if (!$content) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($content);
            $this->entityManager->flush();

            return $this->json(['message' => 'Content deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete content', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/active', name: 'list_active', methods: ['GET'])]
    public function listActive(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Get only active content
        $content = $this->entityManager->getRepository(Content::class)
            ->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->json($content, Response::HTTP_OK, [], ['groups' => ['content:read']]);
    }

}
