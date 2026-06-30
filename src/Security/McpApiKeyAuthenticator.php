<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Autenticatore minimale per proteggere l'endpoint MCP via header X-MCP-Token.
 * Se l'header è presente e combacia con il segreto atteso, assegna ROLE_MCP.
 */
final class McpApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly string $expected)
    {
    }

    public function supports(Request $request): bool
    {
        // Applica solo al path MCP
        return str_starts_with($request->getPathInfo(), '/_mcp');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $provided = $request->headers->get('X-MCP-Token') ?? '';
        if ($this->expected === '' || !\is_string($this->expected)) {
            throw new AuthenticationException('MCP token non configurato sul server.');
        }
        // hash_equals evita timing attacks
        if ($provided === '' || !hash_equals($this->expected, $provided)) {
            throw new AuthenticationException('Invalid MCP token');
        }

        $userId = 'mcp';
        $userLoader = static fn () => new InMemoryUser($userId, null, ['ROLE_MCP']);

        return new SelfValidatingPassport(new UserBadge($userId, $userLoader));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // Niente da fare: si prosegue con la richiesta MCP
        return null;
    }

    public function onAuthenticationFailure(Request $request, \Throwable $exception): ?JsonResponse
    {
        return new JsonResponse(['error' => 'Unauthorized'], 401);
    }
}
