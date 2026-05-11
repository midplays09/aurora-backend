<?php

namespace App\Controller;

use App\Document\User;
use App\Repository\UserRepository;
use App\Security\InputSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly InputSanitizer $sanitizer,
    ) {}

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $email = $this->sanitizer->sanitizeEmail($data['email'] ?? null);
        $plainPassword = $data['password'] ?? '';

        if (!$email) {
            return $this->json(['error' => 'Email is required.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate password strength
        $passwordError = $this->sanitizer->validatePassword($plainPassword);
        if ($passwordError !== null) {
            return $this->json(['error' => $passwordError], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existing = $this->userRepository->findByEmail($email);
        if ($existing !== null) {
            return $this->json(['error' => 'An account with this email already exists.'], Response::HTTP_CONFLICT);
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        // Validate entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['error' => implode(' ', $messages)], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->save($user);

        return $this->json([
            'message' => 'Account created successfully.',
            'user' => $user->toArray(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Login is handled by the JSON login firewall in security.yaml.
     * This endpoint is only here for documentation / route declaration.
     * The actual logic is in Symfony's json_login authenticator.
     */
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This method is never actually called — the firewall intercepts it.
        // If we reach here, authentication failed.
        return $this->json(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['user' => $user->toArray()]);
    }
}
