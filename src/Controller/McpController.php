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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class McpController extends AbstractController
{
    private FilesystemAdapter $cache;

    public function __construct(
        private readonly McpServer $server,
        private readonly bool $mcpEnabled,
        private readonly ?string $mcpToken,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly ?string $mcpLoginEmail = null,
        private readonly ?string $mcpLoginPassword = null,
        private readonly ?UserRepository $userRepository = null,
        private readonly ?UserPasswordHasherInterface $passwordHasher = null,
    ) {
        $this->cache = new FilesystemAdapter('mcp_sessions', 3600);
    }

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

    // Healthcheck and transport discovery
    #[Route('/mcp', name: 'app_mcp_health', methods: ['GET'])]
    public function health(Request $request): Response
    {
        if (!$this->mcpEnabled) {
            return new JsonResponse(['error' => 'MCP disabled'], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse([
            'status' => 'ok',
            'server' => 'tre-emme-backend',
            'transport' => 'streamable-http',
        ], Response::HTTP_OK);
    }

    // SSE endpoint: opens a stream and sends the POST endpoint URL
    #[Route('/mcp/sse', name: 'app_mcp_sse', methods: ['GET', 'POST'])]
    public function sse(Request $request): Response
    {
        if (!$this->mcpEnabled) {
            return new JsonResponse(['error' => 'MCP disabled'], Response::HTTP_NOT_FOUND);
        }

        $sessionId = bin2hex(random_bytes(16));
        $postUrl = $request->getSchemeAndHttpHost() . '/mcp/messages?sessionId=' . $sessionId;

        // Initialize session in cache
        $this->cache->get('mcp_session_' . $sessionId, function (ItemInterface $item) {
            $item->expiresAfter(300);
            return [
                'queue' => [],
                'connected' => true,
            ];
        });

        $response = new StreamedResponse(function () use ($sessionId, $postUrl) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            echo "event: endpoint\n";
            echo "data: " . $postUrl . "\n\n";
            if (ob_get_level() > 0) { ob_flush(); }
            flush();

            $timeout = time() + 300; // 5 minutes
            while (time() < $timeout) {
                $session = $this->cache->get('mcp_session_' . $sessionId, function () { return null; });
                if (!$session || !($session['connected'] ?? false)) {
                    break;
                }
                if (!empty($session['queue'])) {
                    $msg = array_shift($session['queue']);
                    $item = $this->cache->getItem('mcp_session_' . $sessionId);
                    $item->set($session);
                    $this->cache->save($item);

                    echo "event: message\n";
                    echo "data: " . json_encode($msg) . "\n\n";
                    if (ob_get_level() > 0) { ob_flush(); }
                    flush();
                }
                usleep(200000); // 200ms
            }
            $this->cache->delete('mcp_session_' . $sessionId);
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        return $response;
    }

    // Message endpoint used by the SSE transport
    #[Route('/mcp/messages', name: 'app_mcp_messages', methods: ['POST'])]
    public function messages(Request $request): Response
    {
        if (!$this->mcpEnabled) {
            return new JsonResponse(['error' => 'MCP disabled'], Response::HTTP_NOT_FOUND);
        }

        // Reuse the same auth flow as /mcp
        $provided = $this->extractToken($request);
        if (!$this->isValidJwt($provided) && !$this->isValidStaticToken($provided) && !$this->isValidEnvLogin()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent() ?: '[]', true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->server->handleRequestPublic($payload);

        // If sessionId present, push into its queue and return 204
        $sessionId = $request->query->get('sessionId');
        if ($sessionId) {
            $item = $this->cache->getItem('mcp_session_' . $sessionId);
            if ($item->isHit()) {
                $session = $item->get();
                $session['queue'][] = $result;
                $item->set($session);
                $this->cache->save($item);
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

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
