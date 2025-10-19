<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\App;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class OrderNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $appUrl
    ) {
    }

    public function sendOrderNotificationToOwner(Order $order, App $app): void
    {
        // Get the app's email (company email)
        $appEmail = $app->getEmail();
        
        if (!$appEmail) {
            // Fallback to app owner's email if app email is not set
            $appEmail = $app->getOwner()->getEmail();
        }
        
        if (!$appEmail) {
            throw new \Exception('App email not found');
        }

        // Calculate order total
        $orderTotal = 0;
        foreach ($order->getOrderProducts() as $orderProduct) {
            $orderTotal += $orderProduct->getTotalPrice();
        }

        // Create email
        $email = (new Email())
            ->from('noreply@benefitowo.com')
            ->to($appEmail)
            ->subject('Nowe zamówienie w sklepie ' . $app->getTitle())
            ->html($this->twig->render('emails/new_order_notification.html.twig', [
                'order' => $order,
                'app' => $app,
                'orderTotal' => $orderTotal,
                'appUrl' => $this->appUrl
            ]));

        $this->mailer->send($email);
    }
}
