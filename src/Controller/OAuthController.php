<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    public function googleOAuth(Request $request): JsonResponse
    {
        $client = $this->clientRegistry->getClient('google');
        
        // Get the redirect URL from query params (if coming from subdomain)
        $redirectUrl = $request->query->get('redirect_url');
        
        $options = [
            'scope' => ['email', 'profile']
        ];
        
        // Pass redirect URL through state parameter if provided
        if ($redirectUrl) {
            $options['state'] = base64_encode(json_encode([
                'redirect_url' => $redirectUrl
            ]));
        }
        
        $authUrl = $client->getOAuth2Provider()->getAuthorizationUrl($options);

        return new JsonResponse([
            'auth_url' => $authUrl
        ]);
    }

    public function googleOAuthCallback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $client = $this->clientRegistry->getClient('google');
            
            // Get the authorization code from the request
            $code = $request->query->get('code');
            if (!$code) {
                throw new \Exception('Authorization code not found');
            }
            
            // Get the state parameter to extract redirect URL
            $state = $request->query->get('state');
            $redirectUrl = null;
            if ($state) {
                $stateData = json_decode(base64_decode($state), true);
                $redirectUrl = $stateData['redirect_url'] ?? null;
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
            
            // PRIORITY 1: Check if user already exists by Google ID
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['googleId' => $googleId]);
            
            error_log('OAuth: User found by Google ID: ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
            
            if (!$user) {
                // PRIORITY 2: Check if user exists by email (most important!)
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);
                
                error_log('OAuth: User found by email: ' . ($user ? 'Yes (ID: ' . $user->getId() . ')' : 'No'));
                
                if ($user) {
                    // Link existing user with Google ID - DO NOT CREATE NEW USER
                    $user->setGoogleId($googleId);
                    // Verify email for OAuth users since Google has already verified it
                    if ($user->getEmailVerifiedAt() === null) {
                        $user->setEmailVerifiedAt(new \DateTimeImmutable());
                    }
                    error_log('OAuth: Linking existing user with Google ID - ID: ' . $user->getId());
                } else {
                    // Only create new user if NO user exists with this email
                    $user = new User();
                    $user->setEmail($email);
                    $user->setGoogleId($googleId);
                    $user->setFirstName($firstName);
                    $user->setLastName($lastName);
                    $user->setRoles(['ROLE_USER']);
                    // Verify email for OAuth users since Google has already verified it
                    $user->setEmailVerifiedAt(new \DateTimeImmutable());
                    // Set a placeholder password for OAuth users (they can't use it to login)
                    $user->setPassword('OAUTH_USER_NO_PASSWORD');
                    
                    $this->entityManager->persist($user);
                    error_log('OAuth: Creating new user (no existing user found)');
                }
            } else {
                error_log('OAuth: User already exists with Google ID');
            }
            
            // Save changes with error handling
            try {
                $this->entityManager->flush();
                error_log('OAuth: User processed successfully - ID: ' . $user->getId());
            } catch (UniqueConstraintViolationException $e) {
                error_log('OAuth: Unique constraint violation - this should not happen with proper logic');
                error_log('OAuth: Error details: ' . $e->getMessage());
                
                // This should rarely happen with the corrected logic, but handle it gracefully
                $this->entityManager->clear();
                
                // Try to find the existing user that caused the constraint violation
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
                    // Ensure password is set for OAuth users
                    if ($user->getPassword() === null) {
                        $user->setPassword('OAUTH_USER_NO_PASSWORD');
                    }
                    $this->entityManager->flush();
                    error_log('OAuth: Successfully linked existing user after constraint violation - ID: ' . $user->getId());
                } else {
                    throw new \Exception('Unable to find existing user after unique constraint violation');
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
                'googleId' => $user->getGoogleId(), // Include Google OAuth ID
                'roles' => $user->getRoles(),
                'emailVerified' => $user->isEmailVerified(),
                'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s')
            ];
            
            // Get the frontend URL from state or use default
            $frontendUrl = $redirectUrl ?? ($_ENV['APP_URL'] ?? 'https://simplybooking.pl');
            
            // Set the auth token cookie for cross-subdomain access
            $response = $this->redirect($frontendUrl . '/auth/google/callback?success=true');
            
            // Set secure cookie with proper attributes
            $response->headers->setCookie(
                new \Symfony\Component\HttpFoundation\Cookie(
                    'auth_token',
                    $token,
                    time() + (30 * 24 * 60 * 60), // 30 days
                    '/', // path
                    null, // domain (let browser handle subdomain sharing)
                    true, // secure (HTTPS only)
                    true, // httpOnly (prevent XSS)
                    false, // raw
                    'lax' // sameSite
                )
            );
            
            // Also pass user data as URL parameter for frontend processing
            $response->headers->set('X-User-Data', json_encode($userData));
            
            return $response;
            
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('OAuth authentication error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            // Get the frontend URL from state or use default
            $frontendUrl = $redirectUrl ?? ($_ENV['APP_URL'] ?? 'https://simplybooking.pl');
            
            // Redirect to frontend with error message
            $errorRedirectUrl = $frontendUrl . '/auth/google/callback?' . http_build_query([
                'error' => 'OAuth authentication failed',
                'message' => $e->getMessage(),
                'success' => 'false'
            ]);
            
            return $this->redirect($errorRedirectUrl);
        }
    }
}
