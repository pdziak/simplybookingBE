<?php

namespace App\Controller;

use App\Entity\User;
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
            
            // Check if user already exists by Google ID
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['googleId' => $googleId]);
            
            if (!$user) {
                // Check if user exists by email
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);
                
                if ($user) {
                    // Link existing user with Google ID
                    $user->setGoogleId($googleId);
                    // Verify email for OAuth users since Google has already verified it
                    if ($user->getEmailVerifiedAt() === null) {
                        $user->setEmailVerifiedAt(new \DateTimeImmutable());
                    }
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
                }
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
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
            return new JsonResponse([
                'error' => 'OAuth authentication failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
