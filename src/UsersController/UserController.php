<?php

namespace App\UsersController;

use App\DTO\UserRequest;
use App\DTO\UserResponse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Check if user has admin role
        $currentUser = $this->getUser();
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse(['error' => 'Access denied. Admin role required.'], Response::HTTP_FORBIDDEN);
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        $userResponses = array_map(function (User $user) {
            // Prepare assigned apps data for response
            $assignedApps = [];
            foreach ($user->getAssignedApps() as $app) {
                $assignedApps[] = [
                    'id' => $app->getId(),
                    'title' => $app->getTitle(),
                    'slug' => $app->getSlug(),
                    'companyName' => $app->getCompanyName()
                ];
            }

            return new UserResponse(
                $user->getId(),
                $user->getEmail(),
                $user->getLogin(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getRoles(),
                $user->getCreatedAt()->format('Y-m-d H:i:s'),
                $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
                $assignedApps
            );
        }, $users);

        return new JsonResponse($this->serializer->serialize($userResponses, 'json', ['groups' => ['user:read']]), 
            Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        // Allow users to get their own account or require admin role for others
        if ($currentUser->getId() !== $id && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse(['error' => 'Access denied. You can only access your own account or admin role required.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Prepare assigned apps data for response
        $assignedApps = [];
        foreach ($user->getAssignedApps() as $app) {
            $assignedApps[] = [
                'id' => $app->getId(),
                'title' => $app->getTitle(),
                'slug' => $app->getSlug(),
                'companyName' => $app->getCompanyName()
            ];
        }

        $userResponse = new UserResponse(
            $user->getId(),
            $user->getEmail(),
            $user->getLogin(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getRoles(),
            $user->getCreatedAt()->format('Y-m-d H:i:s'),
            $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            $assignedApps
        );

        return new JsonResponse($this->serializer->serialize($userResponse, 'json', ['groups' => ['user:read']]), 
            Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Check if user has admin role
        $currentUser = $this->getUser();
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse(['error' => 'Access denied. Admin role required.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        
        $userRequest = $this->serializer->deserialize(
            json_encode($data),
            UserRequest::class,
            'json'
        );

        $errors = $this->validator->validate($userRequest);
        if (count($errors) > 0) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'details' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists by email
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $userRequest->email]);

        if ($existingUser) {
            return new JsonResponse([
                'error' => 'User with this email already exists'
            ], Response::HTTP_CONFLICT);
        }

        // Check if login is provided and if it already exists
        if ($userRequest->login) {
            $existingUserByLogin = $this->entityManager->getRepository(User::class)
                ->findOneBy(['login' => $userRequest->login]);

            if ($existingUserByLogin) {
                return new JsonResponse([
                    'error' => 'Login is already taken'
                ], Response::HTTP_CONFLICT);
            }
        }

        // Create new user
        $user = new User();
        $user->setEmail($userRequest->email);
        $user->setLogin($userRequest->login);
        $user->setFirstName($userRequest->firstName);
        $user->setLastName($userRequest->lastName);
        $user->setRoles($userRequest->roles);

        // Hash password if provided
        if ($userRequest->password) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userRequest->password);
            $user->setPassword($hashedPassword);
        } else {
            // Generate a random password if none provided
            $randomPassword = bin2hex(random_bytes(8));
            $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
            $user->setPassword($hashedPassword);
        }

        // Handle app assignments
        if (!empty($userRequest->assignedAppIds)) {
            $appRepository = $this->entityManager->getRepository(\App\Entity\App::class);
            foreach ($userRequest->assignedAppIds as $appId) {
                $app = $appRepository->find($appId);
                if ($app) {
                    $user->addAssignedApp($app);
                }
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Prepare assigned apps data for response
        $assignedApps = [];
        foreach ($user->getAssignedApps() as $app) {
            $assignedApps[] = [
                'id' => $app->getId(),
                'title' => $app->getTitle(),
                'slug' => $app->getSlug(),
                'companyName' => $app->getCompanyName()
            ];
        }

        $userResponse = new UserResponse(
            $user->getId(),
            $user->getEmail(),
            $user->getLogin(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getRoles(),
            $user->getCreatedAt()->format('Y-m-d H:i:s'),
            $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            $assignedApps
        );

        return new JsonResponse($this->serializer->serialize($userResponse, 'json', ['groups' => ['user:read']]), 
            Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        // Allow users to update their own account or require admin role for others
        if ($currentUser->getId() !== $id && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse(['error' => 'Access denied. You can only update your own account or admin role required.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        $userRequest = $this->serializer->deserialize(
            json_encode($data),
            UserRequest::class,
            'json'
        );

        $errors = $this->validator->validate($userRequest);
        if (count($errors) > 0) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'details' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if email is being changed and if it already exists
        if ($userRequest->email !== $user->getEmail()) {
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $userRequest->email]);

            if ($existingUser) {
                return new JsonResponse([
                    'error' => 'User with this email already exists'
                ], Response::HTTP_CONFLICT);
            }
        }

        // Check if login is being changed and if it already exists
        if ($userRequest->login && $userRequest->login !== $user->getLogin()) {
            $existingUserByLogin = $this->entityManager->getRepository(User::class)
                ->findOneBy(['login' => $userRequest->login]);

            if ($existingUserByLogin) {
                return new JsonResponse([
                    'error' => 'Login is already taken'
                ], Response::HTTP_CONFLICT);
            }
        }

        // Update user properties
        $user->setEmail($userRequest->email);
        $user->setLogin($userRequest->login);
        $user->setFirstName($userRequest->firstName);
        $user->setLastName($userRequest->lastName);
        
        // Only allow role modification for admin users
        if (in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            $user->setRoles($userRequest->roles);
        }
        // For non-admin users, keep their existing roles
        $user->setUpdatedAt(new \DateTimeImmutable());

        // Update password if provided
        if ($userRequest->password) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userRequest->password);
            $user->setPassword($hashedPassword);
        }

        // Handle app assignments (only for admin users)
        if (in_array('ROLE_ADMIN', $currentUser->getRoles()) && isset($userRequest->assignedAppIds)) {
            // Clear existing assignments
            foreach ($user->getAssignedApps() as $app) {
                $user->removeAssignedApp($app);
            }
            
            // Add new assignments
            if (!empty($userRequest->assignedAppIds)) {
                $appRepository = $this->entityManager->getRepository(\App\Entity\App::class);
                foreach ($userRequest->assignedAppIds as $appId) {
                    $app = $appRepository->find($appId);
                    if ($app) {
                        $user->addAssignedApp($app);
                    }
                }
            }
        }

        $this->entityManager->flush();

        // Prepare assigned apps data for response
        $assignedApps = [];
        foreach ($user->getAssignedApps() as $app) {
            $assignedApps[] = [
                'id' => $app->getId(),
                'title' => $app->getTitle(),
                'slug' => $app->getSlug(),
                'companyName' => $app->getCompanyName()
            ];
        }

        $userResponse = new UserResponse(
            $user->getId(),
            $user->getEmail(),
            $user->getLogin(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getRoles(),
            $user->getCreatedAt()->format('Y-m-d H:i:s'),
            $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            $assignedApps
        );

        return new JsonResponse($this->serializer->serialize($userResponse, 'json', ['groups' => ['user:read']]), 
            Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        // Check if user has admin role
        $currentUser = $this->getUser();
        if (!$currentUser || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse(['error' => 'Access denied. Admin role required.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Prevent deletion of the current user
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser->getId() === $user->getId()) {
            return new JsonResponse([
                'error' => 'Cannot delete your own account'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/apps/{appId}/users', name: 'get_app_users', methods: ['GET'])]
    public function getAppUsers(int $appId): JsonResponse
    {
        // Check if user has admin role or is the app owner
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Find the app
        $app = $this->entityManager->getRepository(\App\Entity\App::class)->find($appId);
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user is admin or app owner
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());
        $isAppOwner = $app->getOwner() === $currentUser;
        
        if (!$isAdmin && !$isAppOwner) {
            return new JsonResponse(['error' => 'Access denied. Admin role or app ownership required.'], Response::HTTP_FORBIDDEN);
        }

        // Get all users assigned to this app
        $assignedUsers = $app->getAssignedUsers();
        
        $userResponses = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'login' => $user->getLogin(),
                'roles' => $user->getRoles(),
                'emailVerified' => $user->isEmailVerified(),
                'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }, $assignedUsers->toArray());

        return new JsonResponse($userResponses, Response::HTTP_OK);
    }

    #[Route('/apps/{appId}/users/search', name: 'search_app_users', methods: ['GET'])]
    public function searchAppUsers(int $appId, Request $request): JsonResponse
    {
        // Check if user has admin role or is the app owner
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Find the app
        $app = $this->entityManager->getRepository(\App\Entity\App::class)->find($appId);
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user is admin or app owner
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());
        $isAppOwner = $app->getOwner() === $currentUser;
        
        if (!$isAdmin && !$isAppOwner) {
            return new JsonResponse(['error' => 'Access denied. Admin role or app ownership required.'], Response::HTTP_FORBIDDEN);
        }

        // Get search parameters
        $query = $request->query->get('q', '');
        $limit = (int) $request->query->get('limit', 10);
        $offset = (int) $request->query->get('offset', 0);

        // Search through ALL users in the system, not just assigned ones
        $userRepository = $this->entityManager->getRepository(User::class);
        
        if (empty($query)) {
            // If no search query, return all users
            $allUsers = $userRepository->findAll();
        } else {
            // Use DQL to search users efficiently
            $qb = $userRepository->createQueryBuilder('u');
            $qb->where('u.email LIKE :query')
               ->orWhere('u.firstName LIKE :query')
               ->orWhere('u.lastName LIKE :query')
               ->orWhere('u.login LIKE :query')
               ->setParameter('query', '%' . $query . '%');
            
            $allUsers = $qb->getQuery()->getResult();
        }
        
        // Filter users based on search query (if not already done by DQL)
        $filteredUsers = [];
        foreach ($allUsers as $user) {
            if (empty($query) || 
                stripos($user->getEmail(), $query) !== false ||
                stripos($user->getFirstName() ?? '', $query) !== false ||
                stripos($user->getLastName() ?? '', $query) !== false ||
                stripos($user->getLogin() ?? '', $query) !== false) {
                $filteredUsers[] = $user;
            }
        }

        // Apply pagination
        $total = count($filteredUsers);
        $paginatedUsers = array_slice($filteredUsers, $offset, $limit);
        
        // Get assigned user IDs for this app
        $assignedUserIds = [];
        foreach ($app->getAssignedUsers() as $assignedUser) {
            $assignedUserIds[] = $assignedUser->getId();
        }

        $userResponses = array_map(function (User $user) use ($assignedUserIds) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'login' => $user->getLogin(),
                'roles' => $user->getRoles(),
                'emailVerified' => $user->isEmailVerified(),
                'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'isAssignedToApp' => in_array($user->getId(), $assignedUserIds)
            ];
        }, $paginatedUsers);

        return new JsonResponse([
            'data' => $userResponses,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total
        ], Response::HTTP_OK);
    }

    #[Route('/apps/{appId}/users', name: 'assign_user_to_app', methods: ['POST'])]
    public function assignUserToApp(int $appId, Request $request): JsonResponse
    {
        // Check if user has admin role or is the app owner
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Find the app
        $app = $this->entityManager->getRepository(\App\Entity\App::class)->find($appId);
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user is admin or app owner
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());
        $isAppOwner = $app->getOwner() === $currentUser;
        
        if (!$isAdmin && !$isAppOwner) {
            return new JsonResponse(['error' => 'Access denied. Admin role or app ownership required.'], Response::HTTP_FORBIDDEN);
        }

        // Get request data
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Request body is required'], Response::HTTP_BAD_REQUEST);
        }

        // Handle both single userId and array of userIds
        $userIds = [];
        if (isset($data['userId'])) {
            $userIds = [(int) $data['userId']];
        } elseif (isset($data['userIds']) && is_array($data['userIds'])) {
            $userIds = array_map('intval', $data['userIds']);
        } else {
            return new JsonResponse(['error' => 'userId or userIds is required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($userIds)) {
            return new JsonResponse(['error' => 'At least one userId is required'], Response::HTTP_BAD_REQUEST);
        }

        $assignedUsers = [];
        $alreadyAssigned = [];
        $notFound = [];

        foreach ($userIds as $userId) {
            // Find the user to assign
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $notFound[] = $userId;
                continue;
            }

            // Check if user is already assigned to this app
            if ($app->getAssignedUsers()->contains($user)) {
                $alreadyAssigned[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'login' => $user->getLogin()
                ];
                continue;
            }

            // Assign user to app
            $app->addAssignedUser($user);
            $assignedUsers[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'login' => $user->getLogin()
            ];
        }

        $this->entityManager->flush();

        $response = [
            'message' => 'User assignment completed',
            'assigned' => $assignedUsers,
            'app' => [
                'id' => $app->getId(),
                'title' => $app->getTitle(),
                'slug' => $app->getSlug()
            ]
        ];

        if (!empty($alreadyAssigned)) {
            $response['alreadyAssigned'] = $alreadyAssigned;
        }

        if (!empty($notFound)) {
            $response['notFound'] = $notFound;
        }

        return new JsonResponse($response, Response::HTTP_CREATED);
    }

    #[Route('/apps/{appId}/users/{userId}', name: 'remove_user_from_app', methods: ['DELETE'])]
    public function removeUserFromApp(int $appId, int $userId): JsonResponse
    {
        // Check if user has admin role or is the app owner
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Find the app
        $app = $this->entityManager->getRepository(\App\Entity\App::class)->find($appId);
        if (!$app) {
            return new JsonResponse(['error' => 'App not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user is admin or app owner
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());
        $isAppOwner = $app->getOwner() === $currentUser;
        
        if (!$isAdmin && !$isAppOwner) {
            return new JsonResponse(['error' => 'Access denied. Admin role or app ownership required.'], Response::HTTP_FORBIDDEN);
        }

        // Find the user to remove
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user is assigned to this app
        if (!$app->getAssignedUsers()->contains($user)) {
            return new JsonResponse(['error' => 'User is not assigned to this app'], Response::HTTP_NOT_FOUND);
        }

        // Remove user from app
        $app->removeAssignedUser($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'User successfully removed from app',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'login' => $user->getLogin()
            ],
            'app' => [
                'id' => $app->getId(),
                'title' => $app->getTitle(),
                'slug' => $app->getSlug()
            ]
        ], Response::HTTP_OK);
    }
}
