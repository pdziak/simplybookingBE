<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/test', name: 'test_')]
class TestEmailController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    #[Route('/email', name: 'email', methods: ['POST'])]
    public function testEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $testEmail = $data['email'] ?? 'test@example.com';

        try {
            // Create a simple test email
            $email = (new Email())
                ->from('noreply@simplybooking.com')
                ->to($testEmail)
                ->subject('Test Email - SimplyBooking')
                ->html('<h1>Test Email</h1><p>This is a test email from SimplyBooking to verify email configuration.</p>');

            $this->mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $testEmail,
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to send test email: ' . $e->getMessage(),
                'details' => $e->getTraceAsString(),
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    #[Route('/mailer-config', name: 'mailer_config', methods: ['GET'])]
    public function getMailerConfig(): JsonResponse
    {
        try {
            // Get mailer configuration (without exposing sensitive data)
            $mailerConfig = [
                'mailer_available' => $this->mailer !== null,
                'mailer_class' => get_class($this->mailer),
                'environment' => $_ENV['APP_ENV'] ?? 'unknown',
                'debug_mode' => $_ENV['APP_DEBUG'] ?? 'unknown',
                'mailer_dsn_configured' => !empty($_ENV['MAILER_DSN']),
                'mailer_dsn_type' => $this->getMailerDsnType($_ENV['MAILER_DSN'] ?? ''),
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ];

            return new JsonResponse($mailerConfig);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to get mailer configuration: ' . $e->getMessage(),
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    private function getMailerDsnType(string $dsn): string
    {
        if (empty($dsn)) {
            return 'not_configured';
        }

        if (str_starts_with($dsn, 'smtp://')) {
            return 'smtp';
        }

        if (str_starts_with($dsn, 'sendmail://')) {
            return 'sendmail';
        }

        if (str_starts_with($dsn, 'null://')) {
            return 'null';
        }

        return 'unknown';
    }
}
