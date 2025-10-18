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
        // Generate verification token
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+24 hours');

        // Store token in user entity
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt($expiresAt);

        // Persist the changes to ensure the token is saved
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate verification URL - point to frontend verification page
        $verificationUrl = $this->appUrl . '/verify-email?token=' . $token;

        // Create email
        $email = (new Email())
            ->from('noreply@benefitowo.com')
            ->to($user->getEmail())
            ->subject('Potwierdź swój adres email - Benefitowo')
            ->html($this->twig->render('emails/verification.html.twig', [
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $expiresAt,
                'appUrl' => $this->appUrl
            ]));

        $this->mailer->send($email);
    }

    public function verifyEmail(string $token): ?User
    {
        // This method will be used by the verification endpoint
        // For now, return null - we'll implement this in the controller
        return null;
    }
}
