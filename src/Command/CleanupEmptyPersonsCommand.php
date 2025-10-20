<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-empty-persons',
    description: 'Clean up empty EventPerson records from the database',
)]
class CleanupEmptyPersonsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cleaning up empty EventPerson records');

        // Find all EventPerson records with empty or null person_fullname
        $query = $this->entityManager->createQuery('
            DELETE FROM App\Entity\EventPerson ep 
            WHERE ep.personFullname IS NULL 
            OR ep.personFullname = \'\' 
            OR TRIM(ep.personFullname) = \'\'
        ');

        $deletedCount = $query->execute();

        $io->success("Cleaned up {$deletedCount} empty EventPerson records");

        return Command::SUCCESS;
    }
}
