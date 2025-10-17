<?php

namespace App\Controller;

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

#[Route('/auth', name: 'api_auth_')]
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
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
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
                'error' => 'Validation failed',
                'details' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            return $response;
        }

        // Check if user already exists by email
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $registerRequest->email]);

        if ($existingUser) {
            $response = new JsonResponse([
                'error' => 'User with this email already exists'
            ], Response::HTTP_CONFLICT);
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            return $response;
        }

        // Check if login is provided and if it already exists
        if ($registerRequest->login) {
            $existingUserByLogin = $this->entityManager->getRepository(User::class)
                ->findOneBy(['login' => $registerRequest->login]);

            if ($existingUserByLogin) {
                $response = new JsonResponse([
                    'error' => 'Login is already taken'
                ], Response::HTTP_CONFLICT);
                $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
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

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send verification email
        try {
            $this->emailVerificationService->sendVerificationEmail($user);
        } catch (\Exception $e) {
            // Log the error but don't fail registration
            // In production, you might want to use a proper logger
            error_log('Failed to send verification email: ' . $e->getMessage());
        }

        // Return success message without JWT token - user needs to verify email first
        $response = new JsonResponse([
            'message' => 'Account created successfully. Please check your email to verify your account.',
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
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }

    #[Route('/login', name: 'login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }
        $data = json_decode($request->getContent(), true);
        
        $loginRequest = $this->serializer->deserialize(
            json_encode($data),
            LoginRequest::class,
            'json'
        );

        $errors = $this->validator->validate($loginRequest);
        if (count($errors) > 0) {
            $response = new JsonResponse([
                'error' => 'Validation failed',
                'details' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            return $response;
        }

        // Find user by email or username using custom user provider
        try {
            $user = $this->userProvider->loadUserByIdentifier($loginRequest->login);
        } catch (\Symfony\Component\Security\Core\Exception\UserNotFoundException $e) {
            $response = new JsonResponse([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            return $response;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $loginRequest->password)) {
            $response = new JsonResponse([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            return $response;
        }

        // Check if user's email is verified - email_verified_at must not be null
        if ($user->getEmailVerifiedAt() === null) {
            $response = new JsonResponse([
                'error' => 'Please verify your email address before logging in. Check your email for verification instructions.',
                'emailVerified' => false
            ], Response::HTTP_FORBIDDEN);
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            return $response;
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);
        $refreshToken = $this->jwtManager->create($user); // In production, use a separate refresh token service

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'login' => $user->getLogin(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'emailVerified' => $user->isEmailVerified(),
            'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s')
        ];

        $response = new AuthResponse($token, $refreshToken, $userData);

        $jsonResponse = new JsonResponse($this->serializer->serialize($response, 'json', ['groups' => ['auth:read']]), 
            Response::HTTP_OK, [], true);
        
        // Add CORS headers
        $jsonResponse->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $jsonResponse->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $jsonResponse->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $jsonResponse->headers->set('Access-Control-Allow-Credentials', 'true');
        
        return $jsonResponse;
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
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
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user's email is verified - email_verified_at must not be null
        if ($user->getEmailVerifiedAt() === null) {
            return new JsonResponse([
                'error' => 'Please verify your email address before accessing this resource.',
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
                'error' => 'Verification token is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find user by verification token
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            // Check if there's a user with this token in the past (already verified)
            // We'll search by email if the token looks like a valid format
            if (strlen($token) === 64 && ctype_xdigit($token)) {
                // This looks like a valid token format, but user might be already verified
                // Let's provide a more helpful message
                return new JsonResponse([
                    'error' => 'This verification link has already been used or has expired. If you need to verify your email, please request a new verification email.',
                    'code' => 'TOKEN_ALREADY_USED'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            return new JsonResponse([
                'error' => 'Invalid verification token'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user is already verified
        if ($user->isEmailVerified()) {
            return new JsonResponse([
                'message' => 'Your email has already been verified! You can log in to your account.',
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
                'error' => 'Verification token has expired. Please request a new verification email.',
                'code' => 'TOKEN_EXPIRED'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verify the user's email
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Email verified successfully! You can now log in to your account.',
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

    #[Route('/resend-verification', name: 'resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse([
                'error' => 'Email address is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find user by email
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user is already verified
        if ($user->isEmailVerified()) {
            return new JsonResponse([
                'error' => 'Email is already verified'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Send new verification email
        try {
            $this->emailVerificationService->sendVerificationEmail($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            error_log('Failed to resend verification email: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Failed to send verification email'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Verification email sent successfully'
        ], Response::HTTP_OK);
    }

    #[Route('/test-cors', name: 'test_cors', methods: ['GET', 'OPTIONS'])]
    public function testCors(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $response = new JsonResponse([
            'message' => 'CORS test successful',
            'origin' => $request->headers->get('Origin')
        ]);
        
        // Add CORS headers
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }

    #[Route('/simple-test', name: 'simple_test', methods: ['GET', 'OPTIONS'])]
    public function simpleTest(Request $request): JsonResponse
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse();
            $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '3600');
            return $response;
        }

        $response = new JsonResponse([
            'message' => 'Simple test successful',
            'origin' => $request->headers->get('Origin'),
            'method' => $request->getMethod()
        ]);
        
        // Add CORS headers
        $response->headers->set('Access-Control-Allow-Origin', 'http://benefitowo.webdev:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }
}
