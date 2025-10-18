<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $adminEmail,
        private string $adminName = 'Benefitowo Team',
        private string $appUrl = ''
    ) {
    }

    public function sendContactFormEmail(string $company, string $email, string $content): bool
    {
        try {
            // Create email to admin
            $adminEmail = (new Email())
                ->from(new Address($email, $company))
                ->to(new Address($this->adminEmail, $this->adminName))
                ->subject('Nowe zapytanie z formularza kontaktowego - ' . $company)
                ->html($this->twig->render('emails/contact_form_admin.html.twig', [
                    'company' => $company,
                    'email' => $email,
                    'content' => $content,
                    'date' => new \DateTimeImmutable()
                ]));

            // Create confirmation email to customer
            $customerEmail = (new Email())
                ->from(new Address($this->adminEmail, $this->adminName))
                ->to(new Address($email, $company))
                ->subject('Potwierdzenie otrzymania zapytania - Benefitowo')
                ->html($this->twig->render('emails/contact_form_customer.html.twig', [
                    'company' => $company,
                    'content' => $content,
                    'date' => new \DateTimeImmutable()
                ]));

            // Send both emails
            $this->mailer->send($adminEmail);
            $this->mailer->send($customerEmail);

            return true;
        } catch (\Exception $e) {
            // Log the error (you might want to use a logger service)
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendTestEmail(): bool
    {
        try {
            $email = (new Email())
                ->from(new Address($this->adminEmail, $this->adminName))
                ->to(new Address($this->adminEmail, $this->adminName))
                ->subject('Test Email - Benefitowo')
                ->html('<h1>Test Email</h1><p>This is a test email from Benefitowo contact system.</p>');

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log('Test email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
