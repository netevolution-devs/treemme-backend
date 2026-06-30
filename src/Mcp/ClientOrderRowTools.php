<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use App\Security\McpTokenProvider;

final class ClientOrderRowTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly McpTokenProvider $tokens,
    ) {}

    #[McpTool(
        name: 'client_order_rows.list',
        title: 'Elenca righe ordine',
        description: 'Lista righe ordine. Usa endpoint /client-order-row senza ID.'
    )]
    public function list(): array
    {
        $request = HttpRequest::create('/client-order-row', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_order_rows.get',
        title: 'Dettaglio riga ordine',
        description: 'Ottiene il dettaglio di una riga ordine per ID.'
    )]
    public function get(int $id): array
    {
        $request = HttpRequest::create('/client-order-row/' . $id, 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_order_rows.create',
        title: 'Crea riga ordine',
        description: 'Crea una nuova riga ordine (form params o JSON).'
    )]
    public function create(array $data): array
    {
        $request = HttpRequest::create('/client-order-row', 'POST', $data);
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_order_rows.update',
        title: 'Aggiorna riga ordine',
        description: 'Aggiorna una riga ordine esistente (PUT).'
    )]
    public function update(int $id, array $data): array
    {
        $request = HttpRequest::create('/client-order-row/' . $id, 'PUT', $data);
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_order_rows.delete',
        title: 'Elimina riga ordine',
        description: 'Elimina una riga ordine esistente per ID.'
    )]
    public function delete(int $id): array
    {
        $request = HttpRequest::create('/client-order-row/' . $id, 'DELETE');
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_order_rows.close',
        title: 'Chiudi riga ordine',
        description: 'Chiude manualmente una riga ordine (PATCH /client-order-row/{id}/close).'
    )]
    public function close(int $id): array
    {
        $request = HttpRequest::create('/client-order-row/' . $id . '/close', 'PATCH');
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}
