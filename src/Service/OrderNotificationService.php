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
        // Get the app owner's email
        $ownerEmail = $app->getOwner()->getEmail();
        
        if (!$ownerEmail) {
            throw new \Exception('App owner email not found');
        }

        // Calculate order total
        $orderTotal = 0;
        foreach ($order->getOrderProducts() as $orderProduct) {
            $orderTotal += $orderProduct->getTotalPrice();
        }

        // Create email
        $email = (new Email())
            ->from('noreply@benefitowo.com')
            ->to($ownerEmail)
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
