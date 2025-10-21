<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailVerificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private string $appUrl
    ) {
    }

    public function sendVerificationEmail(User $user): void
    {
        try {
            // Generate verification token only if one doesn't exist
            if (!$user->getEmailVerificationToken()) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = new \DateTimeImmutable('+24 hours');
                
                // Store token in user entity
                $user->setEmailVerificationToken($token);
                $user->setEmailVerificationTokenExpiresAt($expiresAt);

                // Persist the changes to ensure the token is saved
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            // Generate verification URL - point to frontend verification page
            $verificationUrl = $this->appUrl . '/verify-email?token=' . $user->getEmailVerificationToken();

            // Log email sending attempt
            error_log('Attempting to send verification email to: ' . $user->getEmail());
            error_log('Verification URL: ' . $verificationUrl);
            error_log('App URL: ' . $this->appUrl);

            // Create email
            $email = (new Email())
                ->from('noreply@simplybooking.com')
                ->to($user->getEmail())
                ->subject('Potwierdź swój adres email - SimplyBooking.pl')
                ->html($this->twig->render('emails/verification.html.twig', [
                    'user' => $user,
                    'verificationUrl' => $verificationUrl,
                    'expiresAt' => $user->getEmailVerificationTokenExpiresAt(),
                    'appUrl' => $this->appUrl
                ]));

            $this->mailer->send($email);
            error_log('Verification email sent successfully to: ' . $user->getEmail());
            
        } catch (\Exception $e) {
            error_log('Failed to send verification email to ' . $user->getEmail() . ': ' . $e->getMessage());
            error_log('Email error details: ' . $e->getTraceAsString());
            throw $e; // Re-throw to be handled by the controller
        }
    }

    public function verifyEmail(string $token): ?User
    {
        // This method will be used by the verification endpoint
        // For now, return null - we'll implement this in the controller
        return null;
    }
}
