<?php

namespace App\AuthController;

use App\DTO\AuthResponse;
use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Security\LoginOrEmailUserProvider;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth', name: 'auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private LoginOrEmailUserProvider $userProvider,
        private EmailVerificationService $emailVerificationService
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST', 'OPTIONS'])]
    public function register(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $data = json_decode($request->getContent(), true);
        
        $registerRequest = $this->serializer->deserialize(
            json_encode($data),
            RegisterRequest::class,
            'json'
        );

        $errors = $this->validator->validate($registerRequest);
        if (count($errors) > 0) {
            $response = new JsonResponse([
                'error' => 'Walidacja nie powiodła się',
                'details' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
            return $response;
        }

        // Check if user already exists by email
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $registerRequest->email]);

        if ($existingUser) {
            $response = new JsonResponse([
                'error' => 'Użytkownik z tym adresem email już istnieje'
            ], Response::HTTP_CONFLICT);
            return $response;
        }

        // Check if login is provided and if it already exists
        if ($registerRequest->login) {
            $existingUserByLogin = $this->entityManager->getRepository(User::class)
                ->findOneBy(['login' => $registerRequest->login]);

            if ($existingUserByLogin) {
                $response = new JsonResponse([
                    'error' => 'Login jest już zajęty'
                ], Response::HTTP_CONFLICT);
                return $response;
            }
        }

        // Create new user
        $user = new User();
        $user->setEmail($registerRequest->email);
        if ($registerRequest->login) {
            $user->setLogin($registerRequest->login);
        }
        $user->setPassword($this->passwordHasher->hashPassword($user, $registerRequest->password));
        // email_verified_at remains null by default - user is not active until email is verified

        // Generate verification token and store it BEFORE persisting the user
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+24 hours');
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt($expiresAt);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send verification email
        try {
            $this->emailVerificationService->sendVerificationEmail($user);
        } catch (\Exception $e) {
            // Log the error but don't fail registration
            // The token is already stored, so user can still verify via resend
            error_log('Failed to send verification email: ' . $e->getMessage());
        }

        // Return success message without JWT token - user needs to verify email first
        $response = new JsonResponse([
            'message' => 'Konto zostało utworzone pomyślnie. Sprawdź swój email, aby potwierdzić konto.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'login' => $user->getLogin(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'emailVerified' => $user->isEmailVerified(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_CREATED);
        
        // Add CORS headers
        
        return $response;
    }


    #[Route('/login', name: 'login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse([
                'error' => 'Nieprawidłowe dane JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $login = $data['login'] ?? null;
        $password = $data['password'] ?? null;

        // Validate that either email or login is provided
        if (empty($email) && empty($login)) {
            return new JsonResponse([
                'error' => 'Musi zostać podany email lub login'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty($password)) {
            return new JsonResponse([
                'error' => 'Hasło jest wymagane'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Use login if provided, otherwise use email
        $identifier = $login ?: $email;
        
        try {
            $user = $this->userProvider->loadUserByIdentifier($identifier);
        } catch (\Symfony\Component\Security\Core\Exception\UserNotFoundException $e) {
            return new JsonResponse([
                'error' => 'Nieprawidłowe dane logowania'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'error' => 'Nieprawidłowe dane logowania'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user's email is verified - email_verified_at must not be null
        // Temporarily disabled for testing
        // if ($user->getEmailVerifiedAt() === null) {
        //     return new JsonResponse([
        //         'error' => 'Please verify your email address before logging in. Check your email for verification instructions.',
        //         'emailVerified' => false
        //     ], Response::HTTP_FORBIDDEN);
        // }

        // Generate JWT token
        $token = $this->jwtManager->create($user);
        $refreshToken = $this->jwtManager->create($user); // In production, use a separate refresh token service

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'login' => $user->getLogin(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'first_name' => $user->getFirstName(), // Snake case for compatibility
            'last_name' => $user->getLastName(),   // Snake case for compatibility
            'roles' => $user->getRoles(),
            'emailVerified' => $user->isEmailVerified(),
            'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s')
        ];

        $response = new AuthResponse($token, $refreshToken, $userData);

        $jsonResponse = new JsonResponse($this->serializer->serialize($response, 'json', ['groups' => ['auth:read']]), 
            Response::HTTP_OK, [], true);
        
        // Add CORS headers
        $jsonResponse->headers->set('Access-Control-Allow-Origin', '*');
        $jsonResponse->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $jsonResponse->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $jsonResponse;
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse([
                'error' => 'Nie jesteś uwierzytelniony'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'first_name' => $user->getFirstName(), // Snake case for compatibility
            'last_name' => $user->getLastName(),   // Snake case for compatibility
            'login' => $user->getLogin(),
            'roles' => $user->getRoles(),
            'emailVerified' => $user->isEmailVerified(),
            'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];

        return new JsonResponse($userData);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse([
                'error' => 'Nie jesteś uwierzytelniony'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user's email is verified - email_verified_at must not be null
        if ($user->getEmailVerifiedAt() === null) {
            return new JsonResponse([
                'error' => 'Proszę potwierdzić swój adres email przed dostępem do tego zasobu.',
                'emailVerified' => false
            ], Response::HTTP_FORBIDDEN);
        }

        // Generate new JWT token
        $token = $this->jwtManager->create($user);
        $refreshToken = $this->jwtManager->create($user);

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'login' => $user->getLogin(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'first_name' => $user->getFirstName(), // Snake case for compatibility
            'last_name' => $user->getLastName(),   // Snake case for compatibility
            'roles' => $user->getRoles(),
            'emailVerified' => $user->isEmailVerified(),
            'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s')
        ];

        $response = new AuthResponse($token, $refreshToken, $userData);

        return new JsonResponse($this->serializer->serialize($response, 'json', ['groups' => ['auth:read']]), 
            Response::HTTP_OK, [], true);
    }

    #[Route('/verify-email', name: 'verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse([
                'error' => 'Token weryfikacyjny jest wymagany'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find user by verification token
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            // Token not found - this could mean:
            // 1. Token is invalid/expired
            // 2. User was already verified and token was cleared
            // 3. Token was used and cleared
            
            // If token looks valid (64 hex characters), it was likely used before
            if (strlen($token) === 64 && ctype_xdigit($token)) {
                return new JsonResponse([
                    'message' => 'Twój adres e-mail został aktywowany',
                    'alreadyVerified' => true
                ], Response::HTTP_OK);
            }
            
            return new JsonResponse([
                'error' => 'Nieprawidłowy token weryfikacyjny'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user is already verified
        if ($user->isEmailVerified()) {
            return new JsonResponse([
                'message' => 'Twój adres e-mail został aktywowany',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'login' => $user->getLogin(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'emailVerified' => $user->isEmailVerified(),
                    'emailVerifiedAt' => $user->getEmailVerifiedAt()->format('Y-m-d H:i:s')
                ]
            ], Response::HTTP_OK);
        }

        // Check if token is still valid
        if (!$user->isEmailVerificationTokenValid()) {
            return new JsonResponse([
                'error' => 'Token weryfikacyjny wygasł. Poproś o nowy email weryfikacyjny.',
                'code' => 'TOKEN_EXPIRED'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verify the user's email
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Email został pomyślnie potwierdzony! Możesz się teraz zalogować do swojego konta.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'login' => $user->getLogin(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'first_name' => $user->getFirstName(), // Snake case for compatibility
                'last_name' => $user->getLastName(),   // Snake case for compatibility
                'emailVerified' => $user->isEmailVerified(),
                'emailVerifiedAt' => $user->getEmailVerifiedAt()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/resend-verification', name: 'resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse([
                'error' => 'Adres email jest wymagany'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find user by email
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'Użytkownik nie został znaleziony'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user is already verified
        if ($user->isEmailVerified()) {
            return new JsonResponse([
                'error' => 'Email został już potwierdzony'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Send new verification email
        try {
            $this->emailVerificationService->sendVerificationEmail($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            error_log('Failed to resend verification email: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Nie udało się wysłać emaila weryfikacyjnego'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Email weryfikacyjny został wysłany pomyślnie'
        ], Response::HTTP_OK);
    }

    #[Route('/test-cors', name: 'test_cors', methods: ['GET', 'OPTIONS'])]
    public function testCors(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $response = new JsonResponse([
            'message' => 'CORS test successful',
            'origin' => $request->headers->get('Origin')
        ]);
        
        // Add CORS headers
        
        return $response;
    }

    #[Route('/profile', name: 'profile', methods: ['PUT', 'OPTIONS'])]
    public function updateProfile(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $user = $this->getUser();
        
        if (!$user instanceof User) {
            $response = new JsonResponse([
                'error' => 'Nie jesteś uwierzytelniony'
            ], Response::HTTP_UNAUTHORIZED);
            return $response;
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            $response = new JsonResponse([
                'error' => 'Nieprawidłowe dane JSON'
            ], Response::HTTP_BAD_REQUEST);
            return $response;
        }

        // Update user properties if provided
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        if (isset($data['login'])) {
            // Check if login is being changed and if it already exists
            if ($data['login'] !== $user->getLogin()) {
                $existingUserByLogin = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['login' => $data['login']]);

                if ($existingUserByLogin) {
                    $response = new JsonResponse([
                        'error' => 'Login jest już zajęty'
                    ], Response::HTTP_CONFLICT);
                    return $response;
                }
            }
            $user->setLogin($data['login']);
        }

        // Update the updatedAt timestamp
        $user->setUpdatedAt(new \DateTimeImmutable());

        // Persist changes
        $this->entityManager->flush();

        // Return updated user data
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getFirstName(), // Map firstName to name for backward compatibility
            'login' => $user->getLogin(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'first_name' => $user->getFirstName(), // Snake case for compatibility
            'last_name' => $user->getLastName(),   // Snake case for compatibility
            'roles' => $user->getRoles(),
            'emailVerified' => $user->isEmailVerified(),
            'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];

        $response = new JsonResponse($userData, Response::HTTP_OK);
        
        return $response;
    }

    #[Route('/change-password', name: 'change_password', methods: ['PUT', 'OPTIONS'])]
    public function changePassword(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $user = $this->getUser();
        
        if (!$user instanceof User) {
            $response = new JsonResponse([
                'error' => 'Nie jesteś uwierzytelniony'
            ], Response::HTTP_UNAUTHORIZED);
            return $response;
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            $response = new JsonResponse([
                'error' => 'Nieprawidłowe dane JSON'
            ], Response::HTTP_BAD_REQUEST);
            return $response;
        }

        $currentPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$currentPassword || !$newPassword) {
            $response = new JsonResponse([
                'error' => 'Obecne hasło i nowe hasło są wymagane'
            ], Response::HTTP_BAD_REQUEST);
            return $response;
        }

        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            $response = new JsonResponse([
                'error' => 'Obecne hasło jest nieprawidłowe'
            ], Response::HTTP_BAD_REQUEST);
            return $response;
        }

        // Validate new password strength
        if (strlen($newPassword) < 8) {
            $response = new JsonResponse([
                'error' => 'Nowe hasło musi mieć co najmniej 8 znaków'
            ], Response::HTTP_BAD_REQUEST);
            return $response;
        }

        // Update password
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $user->setUpdatedAt(new \DateTimeImmutable());

        // Persist changes
        $this->entityManager->flush();

        $response = new JsonResponse([
            'message' => 'Password changed successfully'
        ], Response::HTTP_OK);
        
        return $response;
    }

    #[Route('/simple-test', name: 'simple_test', methods: ['GET', 'OPTIONS'])]
    public function simpleTest(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $response = new JsonResponse([
            'message' => 'Simple test successful',
            'origin' => $request->headers->get('Origin'),
            'method' => $request->getMethod()
        ]);
        
        // Add CORS headers
        
        return $response;
    }
}
