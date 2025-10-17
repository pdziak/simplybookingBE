<?php

namespace App\Command;

use App\Message\CreateSubdomainMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:send-notification',
    description: 'Send a notification message to Redis queue',
)]
class SendNotificationCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('subdomain', InputArgument::REQUIRED, 'Subdomain to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $subdomain = $input->getArgument('subdomain');


        // Create and dispatch the message
        $message = new CreateSubdomainMessage($subdomain);
        $this->messageBus->dispatch($message);

        $io->success(sprintf(
            'Notification message dispatched to Redis queue for subdomain "%s"',
            $subdomain
        ));

        $io->note('To process the message, run: php bin/console messenger:consume async -vv');

        return Command::SUCCESS;
    }
}
