<?php

namespace App\Controller;

use App\Mcp\McpServer;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class McpController extends AbstractController
{
    public function __construct(
        private readonly McpServer $server,
        private readonly bool $mcpEnabled,
        private readonly ?string $mcpToken,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly ?string $mcpLoginEmail = null,
        private readonly ?string $mcpLoginPassword = null,
        private readonly ?UserRepository $userRepository = null,
        private readonly ?UserPasswordHasherInterface $passwordHasher = null,
    ) {}

    #[Route('/mcp', name: 'app_mcp', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        if (!$this->mcpEnabled) {
            return new JsonResponse(['error' => 'MCP disabled'], Response::HTTP_NOT_FOUND);
        }

        // Validate access using either:
        // - Bearer JWT (from /login) validated through Lexik JWT encoder
        // - Static API key from env (backward compatible)
        // - Fallback: email/password configurati in .env.local (MCP_LOGIN_EMAIL/MCP_LOGIN_PASSWORD)
        $provided = $this->extractToken($request);
        if (!$this->isValidJwt($provided) && !$this->isValidStaticToken($provided) && !$this->isValidEnvLogin()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable $e) {
            return new JsonResponse([
                'jsonrpc' => '2.0',
                'error' => ['code' => -32700, 'message' => 'Invalid JSON'],
                'id' => null
            ], Response::HTTP_OK);
        }

        $result = $this->server->handleRequestPublic($payload);
        return new JsonResponse($result, isset($result['error']) ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
    }

    private function extractToken(Request $request): ?string
    {
        $auth = $request->headers->get('Authorization') ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return $request->headers->get('X-MCP-Token');
    }

    private function isValidStaticToken(?string $provided): bool
    {
        if (!$this->mcpToken) {
            return false;
        }
        $valids = array_filter(array_map('trim', explode(',', (string)$this->mcpToken)));
        return $provided !== null && in_array($provided, $valids, true);
    }

    private function isValidJwt(?string $provided): bool
    {
        if (!$provided) {
            return false;
        }
        try {
            // Decode also verifies signature/expiration as configured in Lexik bundle
            $payload = $this->jwtEncoder->decode($provided);
            return is_array($payload) && !empty($payload['username'] ?? $payload['sub'] ?? null);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isValidEnvLogin(): bool
    {
        // Only active if both env vars are provided and repository/hasher are available
        if (!$this->mcpLoginEmail || !$this->mcpLoginPassword || !$this->userRepository || !$this->passwordHasher) {
            return false;
        }
        $user = $this->userRepository->findOneBy(['email' => $this->mcpLoginEmail]);
        if (!$user) {
            return false;
        }
        return $this->passwordHasher->isPasswordValid($user, $this->mcpLoginPassword);
    }
}
