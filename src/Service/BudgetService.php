<?php

namespace App\Service;

use App\Entity\Budget;
use App\Entity\User;
use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class BudgetService
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $budgetRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->budgetRepository = $entityManager->getRepository(Budget::class);
    }

    /**
     * Get user's budget for a specific app
     */
    public function getUserBudgetForApp(User $user, App $app): ?Budget
    {
        return $this->budgetRepository->findOneBy([
            'user' => $user,
            'app' => $app
        ]);
    }

    /**
     * Reduce user's budget by a specific amount
     */
    public function reduceBudget(User $user, App $app, float $amount): bool
    {
        $budget = $this->getUserBudgetForApp($user, $app);
        
        if (!$budget) {
            throw new \Exception('Budget not found for user and app');
        }

        $currentBudget = (float) $budget->getBudget();
        
        if ($currentBudget < $amount) {
            throw new \Exception('Insufficient budget. Required: ' . $amount . ', Available: ' . $currentBudget);
        }

        $newBudget = $currentBudget - $amount;
        $budget->setBudget((string) $newBudget);
        $budget->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Add amount to user's budget
     */
    public function addToBudget(User $user, App $app, float $amount): bool
    {
        $budget = $this->getUserBudgetForApp($user, $app);
        
        if (!$budget) {
            throw new \Exception('Budget not found for user and app');
        }

        $currentBudget = (float) $budget->getBudget();
        $newBudget = $currentBudget + $amount;
        
        $budget->setBudget((string) $newBudget);
        $budget->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Set user's budget to a specific amount
     */
    public function setBudget(User $user, App $app, float $amount): bool
    {
        $budget = $this->getUserBudgetForApp($user, $app);
        
        if (!$budget) {
            throw new \Exception('Budget not found for user and app');
        }

        $budget->setBudget((string) $amount);
        $budget->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Check if user has sufficient budget
     */
    public function hasSufficientBudget(User $user, App $app, float $amount): bool
    {
        $budget = $this->getUserBudgetForApp($user, $app);
        
        if (!$budget) {
            return false;
        }

        return (float) $budget->getBudget() >= $amount;
    }

    /**
     * Get user's current budget amount
     */
    public function getBudgetAmount(User $user, App $app): float
    {
        $budget = $this->getUserBudgetForApp($user, $app);
        
        if (!$budget) {
            return 0.0;
        }

        return (float) $budget->getBudget();
    }
}
