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
            } else {
                error_log('OAuth: User already exists with Google ID');
            }
            
            // Try to save changes with error handling for unique constraints
            try {
                $this->entityManager->flush();
                error_log('OAuth: User processed successfully - ID: ' . $user->getId());
            } catch (UniqueConstraintViolationException $e) {
                error_log('OAuth: Unique constraint violation, retrying with existing user');
                error_log('OAuth: Error details: ' . $e->getMessage());
                
                // Clear the entity manager to get fresh data
                $this->entityManager->clear();
                
                // Try multiple strategies to find the existing user
                $user = null;
                
                // Strategy 1: Find by email
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);
                error_log('OAuth: Strategy 1 - Found by email: ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
                
                // Strategy 2: Find by Google ID
                if (!$user) {
                    $user = $this->entityManager->getRepository(User::class)
                        ->findOneBy(['googleId' => $googleId]);
                    error_log('OAuth: Strategy 2 - Found by Google ID: ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
                }
                
                // Strategy 3: Find by email using LIKE (in case of case sensitivity issues)
                if (!$user) {
                    $qb = $this->entityManager->createQueryBuilder();
                    $user = $qb->select('u')
                        ->from(User::class, 'u')
                        ->where('LOWER(u.email) = LOWER(:email)')
                        ->setParameter('email', $email)
                        ->getQuery()
                        ->getOneOrNullResult();
                    error_log('OAuth: Strategy 3 - Found by email (case insensitive): ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
                }
                
                // Strategy 4: Find the most recent user with similar email (last resort)
                if (!$user) {
                    $qb = $this->entityManager->createQueryBuilder();
                    $user = $qb->select('u')
                        ->from(User::class, 'u')
                        ->where('u.email LIKE :emailPattern')
                        ->setParameter('emailPattern', '%' . substr($email, 0, strpos($email, '@')) . '%')
                        ->orderBy('u.id', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
                    error_log('OAuth: Strategy 4 - Found by email pattern: ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
                }
                
                if ($user) {
                    // Update the existing user
                    $user->setGoogleId($googleId);
                    if ($user->getEmailVerifiedAt() === null) {
                        $user->setEmailVerifiedAt(new \DateTimeImmutable());
                    }
                    
                    try {
                        $this->entityManager->flush();
                        error_log('OAuth: Successfully updated existing user - ID: ' . $user->getId());
                    } catch (\Exception $flushError) {
                        error_log('OAuth: Error updating existing user: ' . $flushError->getMessage());
                        // If we still can't update, try to create a new user with a different approach
                        $this->createUserWithFallback($email, $googleId, $firstName, $lastName);
                    }
                } else {
                    error_log('OAuth: No existing user found, attempting to create with fallback');
                    $this->createUserWithFallback($email, $googleId, $firstName, $lastName);
                }
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
    
    /**
     * Create user with fallback strategies when normal creation fails
     */
    private function createUserWithFallback(string $email, string $googleId, ?string $firstName, ?string $lastName): User
    {
        error_log('OAuth: Attempting fallback user creation');
        
        // Strategy 1: Try to create with a slightly modified email
        $fallbackEmail = $email;
        $counter = 1;
        
        while ($counter <= 3) {
            try {
                $user = new User();
                $user->setEmail($fallbackEmail);
                $user->setGoogleId($googleId);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setRoles(['ROLE_USER']);
                $user->setEmailVerifiedAt(new \DateTimeImmutable());
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                error_log('OAuth: Fallback user created successfully with email: ' . $fallbackEmail);
                return $user;
                
            } catch (UniqueConstraintViolationException $e) {
                error_log('OAuth: Fallback attempt ' . $counter . ' failed with email: ' . $fallbackEmail);
                $counter++;
                $fallbackEmail = $email . '+' . $counter;
            } catch (\Exception $e) {
                error_log('OAuth: Fallback user creation failed: ' . $e->getMessage());
                throw new \Exception('Unable to create user even with fallback strategies: ' . $e->getMessage());
            }
        }
        
        // Strategy 2: Try to find any user with the Google ID (maybe it was created but not found earlier)
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['googleId' => $googleId]);
        
        if ($user) {
            error_log('OAuth: Found user with Google ID in fallback');
            return $user;
        }
        
        // Strategy 3: Return a minimal user object (last resort)
        error_log('OAuth: Creating minimal user object as last resort');
        $user = new User();
        $user->setEmail($email);
        $user->setGoogleId($googleId);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        
        // Don't persist this user, just return it for the OAuth flow
        return $user;
    }
}
