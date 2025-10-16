<?php

namespace App\Controller;

use App\Entity\App;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/apps', name: 'app_')]
class AppController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        
        // In development mode, allow access without authentication
        if (!$user && $_ENV['APP_ENV'] !== 'dev') {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // If no user in dev mode, return all apps or empty array
        if (!$user) {
            $apps = $this->entityManager->getRepository(App::class)->findAll();
        } else {
            $apps = $this->entityManager->getRepository(App::class)->findBy(['owner' => $user]);
        }

        $data = array_map(function (App $app) {
            return [
                'id' => $app->getId(),
                'title' => $app->getTitle(),
                'slug' => $app->getSlug(),
                'companyName' => $app->getCompanyName(),
                'email' => $app->getEmail(),
                'description' => $app->getDescription(),
                'logo' => $app->getLogo(),
                'createdAt' => $app->getCreatedAt()?->format('c'),
                'updatedAt' => $app->getUpdatedAt()?->format('c'),
            ];
        }, $apps);

        return $this->json([
            'data' => $data,
            'total' => count($data)
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $app = new App();
        $app->setTitle($data['title'] ?? '');
        $app->setSlug($data['slug'] ?? '');
        $app->setCompanyName($data['companyName'] ?? '');
        $app->setEmail($data['email'] ?? '');
        $app->setDescription($data['description'] ?? null);
        $app->setLogo($data['logo'] ?? '');
        $app->setOwner($user);

        $errors = $this->validator->validate($app);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = [
                    'property' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                    'value' => $error->getInvalidValue()
                ];
            }

            return $this->json([
                'error' => 'Validation failed',
                'violations' => $errorMessages
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($app);
        
        // Assign ROLE_EDITOR to the user when they create their first app
        $userRoles = $user->getRoles();
        if (!in_array('ROLE_EDITOR', $userRoles)) {
            $userRoles[] = 'ROLE_EDITOR';
            $user->setRoles($userRoles);
            $this->entityManager->persist($user);
        }
        
        $this->entityManager->flush();

        return $this->json([
            'id' => $app->getId(),
            'title' => $app->getTitle(),
            'slug' => $app->getSlug(),
            'companyName' => $app->getCompanyName(),
            'email' => $app->getEmail(),
            'description' => $app->getDescription(),
            'logo' => $app->getLogo(),
            'createdAt' => $app->getCreatedAt()?->format('c'),
            'updatedAt' => $app->getUpdatedAt()?->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $app = $this->entityManager->getRepository(App::class)->find($id);

        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $app->getId(),
            'title' => $app->getTitle(),
            'slug' => $app->getSlug(),
            'companyName' => $app->getCompanyName(),
            'email' => $app->getEmail(),
            'description' => $app->getDescription(),
            'logo' => $app->getLogo(),
            'createdAt' => $app->getCreatedAt()?->format('c'),
            'updatedAt' => $app->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $app = $this->entityManager->getRepository(App::class)->find($id);

        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) {
            $app->setTitle($data['title']);
        }
        if (isset($data['slug'])) {
            $app->setSlug($data['slug']);
        }
        if (isset($data['companyName'])) {
            $app->setCompanyName($data['companyName']);
        }
        if (isset($data['email'])) {
            $app->setEmail($data['email']);
        }
        if (isset($data['description'])) {
            $app->setDescription($data['description']);
        }
        if (isset($data['logo'])) {
            $app->setLogo($data['logo']);
        }

        $errors = $this->validator->validate($app);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = [
                    'property' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                    'value' => $error->getInvalidValue()
                ];
            }

            return $this->json([
                'error' => 'Validation failed',
                'violations' => $errorMessages
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return $this->json([
            'id' => $app->getId(),
            'title' => $app->getTitle(),
            'slug' => $app->getSlug(),
            'companyName' => $app->getCompanyName(),
            'email' => $app->getEmail(),
            'description' => $app->getDescription(),
            'logo' => $app->getLogo(),
            'createdAt' => $app->getCreatedAt()?->format('c'),
            'updatedAt' => $app->getUpdatedAt()?->format('c'),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $app = $this->entityManager->getRepository(App::class)->find($id);

        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($app);
        $this->entityManager->flush();

        return $this->json(['message' => 'App deleted successfully']);
    }

    #[Route('/{id}/users', name: 'get_users', methods: ['GET'])]
    public function getUsers(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the app or has admin role
        if ($app->getOwner() !== $currentUser && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied. You can only manage users for your own apps.'], Response::HTTP_FORBIDDEN);
        }

        $assignedUsers = $app->getAssignedUsers();
        $userData = [];

        foreach ($assignedUsers as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'login' => $user->getLogin(),
                'roles' => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'data' => $userData,
            'total' => count($userData)
        ]);
    }

    #[Route('/{id}/users', name: 'assign_users', methods: ['POST'])]
    public function assignUsers(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the app or has admin role
        if ($app->getOwner() !== $currentUser && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied. You can only manage users for your own apps.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['userIds']) || !is_array($data['userIds'])) {
            return $this->json(['error' => 'Invalid request. userIds array is required.'], Response::HTTP_BAD_REQUEST);
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $assignedCount = 0;

        foreach ($data['userIds'] as $userId) {
            $user = $userRepository->find($userId);
            if ($user && !$app->getAssignedUsers()->contains($user)) {
                $app->addAssignedUser($user);
                $assignedCount++;
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => "Successfully assigned {$assignedCount} users to the app",
            'assignedCount' => $assignedCount
        ]);
    }

    #[Route('/{id}/users/{userId}', name: 'remove_user', methods: ['DELETE'])]
    public function removeUser(int $id, int $userId): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the app or has admin role
        if ($app->getOwner() !== $currentUser && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied. You can only manage users for your own apps.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($app->getAssignedUsers()->contains($user)) {
            $app->removeAssignedUser($user);
            $this->entityManager->flush();
            return $this->json(['message' => 'User removed from app successfully']);
        }

        return $this->json(['message' => 'User was not assigned to this app'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}/users/search', name: 'search_users', methods: ['GET'])]
    public function searchUsers(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the app or has admin role
        if ($app->getOwner() !== $currentUser && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied. You can only manage users for your own apps.'], Response::HTTP_FORBIDDEN);
        }

        $query = $request->query->get('q', '');
        $limit = (int) $request->query->get('limit', 10);

        $userRepository = $this->entityManager->getRepository(User::class);
        $qb = $userRepository->createQueryBuilder('u');

        if (!empty($query)) {
            $qb->where('u.email LIKE :query OR u.firstName LIKE :query OR u.lastName LIKE :query OR u.login LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $qb->setMaxResults($limit);
        $users = $qb->getQuery()->getResult();

        $userData = [];
        foreach ($users as $user) {
            $isAssigned = $app->getAssignedUsers()->contains($user);
            $userData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'login' => $user->getLogin(),
                'roles' => $user->getRoles(),
                'isAssigned' => $isAssigned,
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'data' => $userData,
            'total' => count($userData)
        ]);
    }

    #[Route('/{id}/users/create', name: 'create_user', methods: ['POST'])]
    public function createUser(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $app = $this->entityManager->getRepository(App::class)->find($id);
        if (!$app) {
            return $this->json(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the app or has admin role
        if ($app->getOwner() !== $currentUser && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return $this->json(['error' => 'Access denied. You can only create users for your own apps.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists by email
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            // If user exists, just assign them to the app if not already assigned
            if (!$app->getAssignedUsers()->contains($existingUser)) {
                $app->addAssignedUser($existingUser);
                $this->entityManager->flush();
            }
            
            return $this->json([
                'message' => 'User already exists and has been assigned to the app',
                'user' => [
                    'id' => $existingUser->getId(),
                    'email' => $existingUser->getEmail(),
                    'firstName' => $existingUser->getFirstName(),
                    'lastName' => $existingUser->getLastName(),
                    'login' => $existingUser->getLogin(),
                    'roles' => $existingUser->getRoles(),
                    'createdAt' => $existingUser->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $existingUser->getUpdatedAt()?->format('Y-m-d H:i:s'),
                ]
            ]);
        }

        // Create new user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setLogin($data['login'] ?? null);
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);
        $user->setRoles(['ROLE_USER']); // Default role for new users

        // Generate a random password
        $randomPassword = bin2hex(random_bytes(8));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        
        // Assign user to the app
        $app->addAssignedUser($user);
        
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User created and assigned to the app successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'login' => $user->getLogin(),
                'roles' => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }
}
