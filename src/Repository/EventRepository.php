<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Find events by user ID
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.datetime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.datetime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.datetime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming events
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.datetime > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.datetime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by location
     */
    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.location LIKE :location')
            ->setParameter('location', '%' . $location . '%')
            ->orderBy('e.datetime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search events by title or description (case insensitive)
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('LOWER(e.title) LIKE LOWER(:query) OR LOWER(e.description) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('e.datetime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

