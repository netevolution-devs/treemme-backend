<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Autenticazione per l'endpoint MCP del bundle su /mcp.
 * Consente: Bearer JWT valido (Lexik), API key da MCP_TOKEN, fallback dev (.env) email/password.
 */
class McpAuthListener
{
    public function __construct(
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly ?string $mcpToken = null,
        private readonly ?string $mcpLoginEmail = null,
        private readonly ?string $mcpLoginPassword = null,
        private readonly ?UserRepository $userRepository = null,
        private readonly ?UserPasswordHasherInterface $passwordHasher = null,
        private readonly bool $mcpEnabled = false,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->mcpEnabled) {
            return; // disabilitato globalmente
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo() ?? '';

        // Applica solo al path /mcp del bundle (non ai legacy se presenti)
        if (0 !== strpos($path, '/mcp')) {
            return;
        }

        // Consenti le preflight e gli healthcheck GET senza body, ma richiedi auth per le POST
        if ($request->isMethod('OPTIONS')) {
            return;
        }

        $authz = $request->headers->get('Authorization') ?? '';
        $bearer = null;
        if (str_starts_with($authz, 'Bearer ')) {
            $bearer = substr($authz, 7);
        } elseif ($request->headers->has('X-MCP-Token')) {
            $bearer = $request->headers->get('X-MCP-Token');
        }

        // 1) JWT valido
        if ($bearer && $this->isValidJwt($bearer)) {
            return;
        }

        // 2) API key statica
        if ($bearer && $this->isValidStaticToken($bearer)) {
            return;
        }

        // 3) Fallback da env (dev/test): email/password
        if ($this->isValidEnvLogin()) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED));
    }

    private function isValidJwt(?string $token): bool
    {
        if (!$token) return false;
        try {
            $payload = $this->jwtEncoder->decode($token);
            return is_array($payload) && (!empty($payload['username'] ?? null) || !empty($payload['sub'] ?? null));
        } catch (\Throwable) {
            return false;
        }
    }

    private function isValidStaticToken(?string $provided): bool
    {
        if (!$this->mcpToken || !$provided) return false;
        $valids = array_filter(array_map('trim', explode(',', (string)$this->mcpToken)));
        return in_array($provided, $valids, true);
    }

    private function isValidEnvLogin(): bool
    {
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
