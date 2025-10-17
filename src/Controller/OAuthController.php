<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class OAuthController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private SerializerInterface $serializer
    ) {
    }

    public function googleOAuth(): JsonResponse
    {
        $client = $this->clientRegistry->getClient('google');
        $authUrl = $client->getOAuth2Provider()->getAuthorizationUrl([
            'scope' => ['email', 'profile']
        ]);

        return new JsonResponse([
            'auth_url' => $authUrl
        ]);
    }

    public function googleOAuthCallback(Request $request): JsonResponse
    {
        try {
            $client = $this->clientRegistry->getClient('google');
            
            // Get the authorization code from the request
            $code = $request->query->get('code');
            if (!$code) {
                throw new \Exception('Authorization code not found');
            }
            
            // Exchange code for access token
            $accessToken = $client->getOAuth2Provider()->getAccessToken('authorization_code', [
                'code' => $code
            ]);
            
            // Get user info from Google
            $googleUser = $client->fetchUserFromToken($accessToken);
            
            $googleId = $googleUser->getId();
            $email = $googleUser->getEmail();
            $firstName = $googleUser->getFirstName();
            $lastName = $googleUser->getLastName();
            
            error_log('OAuth: Processing user - Google ID: ' . $googleId . ', Email: ' . $email);
            
            // Start transaction
            $this->entityManager->beginTransaction();
            
            try {
                // Check if user already exists by Google ID
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['googleId' => $googleId]);
                
                error_log('OAuth: User found by Google ID: ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
                
                if (!$user) {
                    // Check if user exists by email
                    $user = $this->entityManager->getRepository(User::class)
                        ->findOneBy(['email' => $email]);
                    
                    error_log('OAuth: User found by email: ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
                    
                    if ($user) {
                        // Link existing user with Google ID
                        $user->setGoogleId($googleId);
                        // Verify email for OAuth users since Google has already verified it
                        if ($user->getEmailVerifiedAt() === null) {
                            $user->setEmailVerifiedAt(new \DateTimeImmutable());
                        }
                        error_log('OAuth: Updating existing user with Google ID');
                    } else {
                        // Create new user - but first check again in case of race condition
                        $existingUser = $this->entityManager->getRepository(User::class)
                            ->findOneBy(['email' => $email]);
                        
                        if ($existingUser) {
                            // User was created by another process, use that user
                            $user = $existingUser;
                            $user->setGoogleId($googleId);
                            if ($user->getEmailVerifiedAt() === null) {
                                $user->setEmailVerifiedAt(new \DateTimeImmutable());
                            }
                            error_log('OAuth: User was created by another process, linking Google ID');
                        } else {
                            // Create new user
                            $user = new User();
                            $user->setEmail($email);
                            $user->setGoogleId($googleId);
                            $user->setFirstName($firstName);
                            $user->setLastName($lastName);
                            $user->setRoles(['ROLE_USER']);
                            // Verify email for OAuth users since Google has already verified it
                            $user->setEmailVerifiedAt(new \DateTimeImmutable());
                            // No password needed for OAuth users
                            
                            $this->entityManager->persist($user);
                            error_log('OAuth: Creating new user');
                        }
                    }
                } else {
                    error_log('OAuth: User already exists with Google ID');
                }
                
                // Flush changes with specific error handling for unique constraints
                try {
                    $this->entityManager->flush();
                    $this->entityManager->commit();
                    error_log('OAuth: User processed successfully - ID: ' . $user->getId());
                } catch (UniqueConstraintViolationException $e) {
                    $this->entityManager->rollback();
                    error_log('OAuth: Unique constraint violation, retrying with existing user');
                    
                    // If we get a unique constraint violation, try to find the existing user
                    $this->entityManager->beginTransaction();
                    $user = $this->entityManager->getRepository(User::class)
                        ->findOneBy(['email' => $email]);
                    
                    if (!$user) {
                        $user = $this->entityManager->getRepository(User::class)
                            ->findOneBy(['googleId' => $googleId]);
                    }
                    
                    if ($user) {
                        // Update the existing user
                        $user->setGoogleId($googleId);
                        if ($user->getEmailVerifiedAt() === null) {
                            $user->setEmailVerifiedAt(new \DateTimeImmutable());
                        }
                        $this->entityManager->flush();
                        $this->entityManager->commit();
                        error_log('OAuth: Successfully updated existing user - ID: ' . $user->getId());
                    } else {
                        $this->entityManager->rollback();
                        throw new \Exception('Unable to create or find user after unique constraint violation');
                    }
                }
                
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                error_log('OAuth: Transaction rolled back due to error: ' . $e->getMessage());
                throw $e;
            }
            
            // Generate JWT token
            $token = $this->jwtManager->create($user);
            
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
            
            return new JsonResponse([
                'token' => $token,
                'user' => $userData
            ]);
            
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('OAuth authentication error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return new JsonResponse([
                'error' => 'OAuth authentication failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
