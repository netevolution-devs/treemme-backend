<?php

namespace App\Security;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fornisce un JWT "service account" per le richieste dei tool MCP.
 * Ottiene il token chiamando /login con credenziali lette da env.
 */
final class McpTokenProvider
{
    private ?string $jwt = null;
    private ?int $exp = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient, // Sostituito HttpKernelInterface con HttpClientInterface
        private readonly string $email,
        private readonly string $password,
        private readonly bool $useRefresh = false,
    ) {
    }

    /**
     * Restituisce un token valido, eseguendo login/refresh se necessario.
     */
    public function getToken(): string
    {
        if ($this->jwt && !$this->isExpiring()) {
            return $this->jwt;
        }

        if ($this->useRefresh && $this->jwt) {
            if ($this->tryRefresh()) {
                return $this->jwt;
            }
        }

        $this->login();
        return (string) $this->jwt;
    }

    private function isExpiring(): bool
    {
        if (!$this->exp) {
            return true;
        }
        return (time() + 60) >= $this->exp;
    }

    private function login(): void
    {
        try {
            // Effettua una vera chiamata HTTP POST verso l'endpoint assoluto di produzione
            $response = $this->httpClient->request('POST', 'https://api.treemme.netevolution.it/login', [
                'json' => [
                    'email' => $this->email,
                    'password' => $this->password,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException('Il server di autenticazione ha risposto con codice ' . $response->getStatusCode());
            }

            $data = $response->toArray();
            $this->jwt = $data['token'] ?? $data['jwt'] ?? null;
            $this->exp = isset($data['exp']) ? (int) $data['exp'] : null;

        } catch (\Throwable $e) {
            throw new \RuntimeException('Impossibile ottenere JWT dal /login per MCP: ' . $e->getMessage(), 0, $e);
        }

        if (!$this->jwt) {
            throw new \RuntimeException('Impossimize ottenere il token JWT: payload di risposta non valido.');
        }
    }

    private function tryRefresh(): bool
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.treemme.netevolution.it/api/token/refresh', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwt,
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return false;
            }

            $data = $response->toArray();
            $new = $data['token'] ?? $data['jwt'] ?? null;
            if ($new) {
                $this->jwt = $new;
                $this->exp = isset($data['exp']) ? (int) $data['exp'] : null;
                return true;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
}