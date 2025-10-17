<?php

namespace App\MessageHandler;

use App\Message\CreateSubdomainMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateSubdomainMessageHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CreateSubdomainMessage $message): void
    {
        $this->logger->info('Processing notification message', [
            'subdomain' => $message->getSubdomain(),
        ]);

        $name = $message->getSubdomain();
        $output = shell_exec("/home/benefitowopl/create_subdomain.sh " . escapeshellarg($name) . " 2>&1");
        echo "<pre>$output</pre>";

        echo sprintf(
            "Notification sent to subdomain: %s\n",
            $message->getSubdomain()
        );
    }
}
