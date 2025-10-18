<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\User;
use App\Entity\App;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/budgets')]
class BudgetController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'budget_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $budgets = $this->entityManager->getRepository(Budget::class)->findAll();

        return new JsonResponse([
            'data' => $budgets,
            'message' => 'Wszystkie budżety zostały pomyślnie pobrane'
        ]);
    }

    #[Route('/user', name: 'budget_user', methods: ['GET'])]
    public function getUserBudgets(): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Użytkownik nie uwierzytelniony'], 401);
        }

        $budgets = $this->entityManager->getRepository(Budget::class)
            ->findBy(['user' => $user]);

        return new JsonResponse([
            'data' => $budgets,
            'message' => 'Budżety użytkownika zostały pomyślnie pobrane'
        ]);
    }

    #[Route('/app/{appId}', name: 'budget_app', methods: ['GET'])]
    public function getAppBudgets(string $appId): JsonResponse
    {
        $app = $this->entityManager->getRepository(App::class)->find((int) $appId);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], 404);
        }

        $budgets = $this->entityManager->getRepository(Budget::class)
            ->findBy(['app' => $app]);

        return new JsonResponse([
            'data' => $budgets,
            'message' => 'Budżety aplikacji zostały pomyślnie pobrane'
        ]);
    }

    #[Route('/app/{appId}/user', name: 'budget_app_user', methods: ['GET'])]
    public function getBudgetForApp(string $appId): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Użytkownik nie uwierzytelniony'], 401);
        }

        $app = $this->entityManager->getRepository(App::class)->find((int) $appId);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], 404);
        }

        $budget = $this->entityManager->getRepository(Budget::class)
            ->findOneBy(['user' => $user, 'app' => $app]);

        if (!$budget) {
            return new JsonResponse(['error' => 'Brak ustawionego budżetu dla tej aplikacji'], 404);
        }

        // Manually serialize the budget to ensure proper structure
        $budgetData = [
            'id' => $budget->getId(),
            'userId' => $budget->getUser()->getId(),
            'appId' => $budget->getApp()->getId(),
            'budgetAmount' => $budget->getBudgetAmount(),
            'createdAt' => $budget->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $budget->getUpdatedAt() ? $budget->getUpdatedAt()->format('Y-m-d\TH:i:s\Z') : null
        ];

        return new JsonResponse($budgetData);
    }

    #[Route('/app/{appId}/user/{userId}', name: 'budget_app_user_specific', methods: ['GET'])]
    public function getUserBudgetForApp(string $appId, string $userId): JsonResponse
    {
        $app = $this->entityManager->getRepository(App::class)->find((int) $appId);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], 404);
        }

        $user = $this->entityManager->getRepository(User::class)->find((int) $userId);
        
        if (!$user) {
            return new JsonResponse(['error' => 'Użytkownik nie znaleziony'], 404);
        }

        $budget = $this->entityManager->getRepository(Budget::class)
            ->findOneBy(['user' => $user, 'app' => $app]);

        if (!$budget) {
            return new JsonResponse(['error' => 'Brak ustawionego budżetu dla tego użytkownika i aplikacji'], 404);
        }

        // Debug: Log the budget data
        error_log('Budget found: ID=' . $budget->getId() . ', Amount=' . $budget->getBudgetAmount() . ', User=' . $user->getId() . ', App=' . $app->getId());

        // Manually serialize the budget to ensure proper structure
        $budgetData = [
            'id' => $budget->getId(),
            'userId' => $budget->getUser()->getId(),
            'appId' => $budget->getApp()->getId(),
            'budgetAmount' => $budget->getBudgetAmount(),
            'createdAt' => $budget->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $budget->getUpdatedAt() ? $budget->getUpdatedAt()->format('Y-m-d\TH:i:s\Z') : null
        ];

        return new JsonResponse($budgetData);
    }

    #[Route('', name: 'budget_create', methods: ['POST'])]
    public function createBudget(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Użytkownik nie uwierzytelniony'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['appId']) || !isset($data['budget']) || !isset($data['userId'])) {
            return new JsonResponse(['error' => 'appId, userId and budget are required'], 400);
        }

        // Get the user for whom the budget is being created
        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        
        if (!$user) {
            return new JsonResponse(['error' => 'Użytkownik nie znaleziony'], 404);
        }

        $app = $this->entityManager->getRepository(App::class)->find($data['appId']);
        
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], 404);
        }

        // Check if budget already exists for this user and app
        $existingBudget = $this->entityManager->getRepository(Budget::class)
            ->findOneBy(['user' => $user, 'app' => $app]);

        if ($existingBudget) {
            // Instead of returning an error, update the existing budget
            $existingBudget->setBudget((string) $data['budget']);
            
            $errors = $this->validator->validate($existingBudget);
            
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
            }

            $this->entityManager->flush();

            // Manually serialize the updated budget
            $budgetData = [
                'id' => $existingBudget->getId(),
                'userId' => $existingBudget->getUser()->getId(),
                'appId' => $existingBudget->getApp()->getId(),
                'budgetAmount' => $existingBudget->getBudgetAmount(),
                'createdAt' => $existingBudget->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
                'updatedAt' => $existingBudget->getUpdatedAt() ? $existingBudget->getUpdatedAt()->format('Y-m-d\TH:i:s\Z') : null
            ];

            return new JsonResponse($budgetData, 200);
        }

        $budget = new Budget();
        $budget->setUser($user);
        $budget->setApp($app);
        $budget->setBudget((string) $data['budget']);

        $errors = $this->validator->validate($budget);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
        }

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        // Manually serialize the created budget
        $budgetData = [
            'id' => $budget->getId(),
            'userId' => $budget->getUser()->getId(),
            'appId' => $budget->getApp()->getId(),
            'budgetAmount' => $budget->getBudgetAmount(),
            'createdAt' => $budget->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $budget->getUpdatedAt() ? $budget->getUpdatedAt()->format('Y-m-d\TH:i:s\Z') : null
        ];

        return new JsonResponse($budgetData, 201);
    }

    #[Route('/{id}', name: 'budget_update', methods: ['PUT'])]
    public function updateBudget(string $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Użytkownik nie uwierzytelniony'], 401);
        }

        $budgetId = (int) $id;
        $budget = $this->entityManager->getRepository(Budget::class)->find($budgetId);
        
        if (!$budget) {
            return new JsonResponse(['error' => 'Budget not found'], 404);
        }

        // Check if user owns this budget
        if ($budget->getUser() !== $user) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['budget'])) {
            $budget->setBudget((string) $data['budget']);
        }

        $errors = $this->validator->validate($budget);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => 'Validation failed', 'details' => $errorMessages], 400);
        }

        $this->entityManager->flush();

        // Manually serialize the updated budget
        $budgetData = [
            'id' => $budget->getId(),
            'userId' => $budget->getUser()->getId(),
            'appId' => $budget->getApp()->getId(),
            'budgetAmount' => $budget->getBudgetAmount(),
            'createdAt' => $budget->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $budget->getUpdatedAt() ? $budget->getUpdatedAt()->format('Y-m-d\TH:i:s\Z') : null
        ];

        return new JsonResponse($budgetData, 200);
    }

    #[Route('/{id}', name: 'budget_delete', methods: ['DELETE'])]
    public function deleteBudget(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Użytkownik nie uwierzytelniony'], 401);
        }

        $budget = $this->entityManager->getRepository(Budget::class)->find((int) $id);
        
        if (!$budget) {
            return new JsonResponse(['error' => 'Budget not found'], 404);
        }

        // Check if user owns this budget
        if ($budget->getUser() !== $user) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $this->entityManager->remove($budget);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Budget deleted successfully'
        ]);
    }
}
