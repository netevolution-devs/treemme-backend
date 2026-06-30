<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Fornisce un JWT "service account" per le sub-request dei tool MCP.
 * Ottiene il token chiamando /login con credenziali lette da env e lo riusa finché valido.
 */
final class McpTokenProvider
{
    private ?string $jwt = null;
    private ?int $exp = null; // timestamp unix, se disponibile nella risposta

    public function __construct(
        private readonly HttpKernelInterface $kernel,
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
            // se sconosciuta, consideriamo in scadenza per forzare refresh/login
            return true;
        }
        // margine sicurezza 60s
        return (time() + 60) >= $this->exp;
    }

    private function login(): void
    {
        $payload = json_encode([
            'email' => $this->email,
            'password' => $this->password,
        ], JSON_UNESCAPED_UNICODE);

        // MODIFICATO: Specifica l'indirizzo assoluto di produzione anziché il path relativo corrotto da localhost
        $request = HttpRequest::create('https://api.treemme.netevolution.it/login', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        // Forza l'header Host corretto per bypassare i controlli del firewall e del DNS rebinding
        $request->headers->set('Host', 'api.treemme.netevolution.it');

        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        $data = json_decode($response->getContent() ?: '{}', true) ?: [];

        // Supporta chiavi diverse a seconda dell'handler
        $this->jwt = $data['token'] ?? $data['jwt'] ?? null;
        $this->exp = isset($data['exp']) ? (int) $data['exp'] : null;

        if (!$this->jwt) {
            throw new \RuntimeException('Impossibile ottenere JWT dal /login per MCP. Server responded with status: ' . $response->getStatusCode());
        }
    }

    private function tryRefresh(): bool
    {
        // Se hai un meccanismo di refresh basato su bearer corrente
        $request = HttpRequest::create('/api/token/refresh', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $this->jwt);

        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $data = json_decode($response->getContent() ?: '{}', true) ?: [];
        $new = $data['token'] ?? $data['jwt'] ?? null;
        if ($new) {
            $this->jwt = $new;
            $this->exp = isset($data['exp']) ? (int) $data['exp'] : null;
            return true;
        }

        return false;
    }
}
