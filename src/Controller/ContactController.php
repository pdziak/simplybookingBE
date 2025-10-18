<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/contact', name: 'contact_')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private EmailService $emailService
    ) {
    }

    #[Route('', name: 'submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Create contact entity
        $contact = new Contact();
        $contact->setCompany($data['company'] ?? '');
        $contact->setEmail($data['email'] ?? '');
        $contact->setContent($data['content'] ?? '');

        // Validate the contact
        $errors = $this->validator->validate($contact);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Save to database
            $this->entityManager->persist($contact);
            $this->entityManager->flush();

            // Send email
            $emailSent = $this->emailService->sendContactFormEmail(
                $contact->getCompany(),
                $contact->getEmail(),
                $contact->getContent()
            );

            if ($emailSent) {
                $contact->setIsProcessed(true);
                $contact->setProcessedAt(new \DateTimeImmutable());
                $this->entityManager->flush();
            }

            return $this->json([
                'message' => 'Contact form submitted successfully',
                'id' => $contact->getId(),
                'email_sent' => $emailSent
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to submit contact form',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/test-email', name: 'test_email', methods: ['POST'])]
    public function testEmail(): JsonResponse
    {
        try {
            $emailSent = $this->emailService->sendTestEmail();
            
            if ($emailSent) {
                return $this->json(['message' => 'Test email sent successfully'], Response::HTTP_OK);
            } else {
                return $this->json(['error' => 'Failed to send test email'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to send test email',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Get all contact submissions
        $contacts = $this->entityManager->getRepository(Contact::class)
            ->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->json($contacts, Response::HTTP_OK, [], ['groups' => ['contact:read']]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $contact = $this->entityManager->getRepository(Contact::class)->find($id);
        
        if (!$contact) {
            return $this->json(['error' => 'Contact not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($contact, Response::HTTP_OK, [], ['groups' => ['contact:read']]);
    }

    #[Route('/{id}/mark-processed', name: 'mark_processed', methods: ['PATCH'])]
    public function markProcessed(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $contact = $this->entityManager->getRepository(Contact::class)->find($id);
        
        if (!$contact) {
            return $this->json(['error' => 'Contact not found'], Response::HTTP_NOT_FOUND);
        }

        $contact->setIsProcessed(true);
        $contact->setProcessedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();

        return $this->json(['message' => 'Contact marked as processed'], Response::HTTP_OK);
    }
}
