<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use App\Security\McpTokenProvider;

final class ClientOrderTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly McpTokenProvider $tokens,
    ) {}

    #[McpTool(
        name: 'client_orders.list',
        title: 'Elenca ordini cliente',
        description: 'Lista ordini. Filtri opzionali: order_number (string), client (id).'
    )]
    public function list(?string $order_number = null, ?int $client = null): array
    {
        $query = [];
        if ($order_number !== null && $order_number !== '') {
            $query['order_number'] = $order_number;
        }
        if (null !== $client) {
            $query['client'] = $client;
        }
        $request = HttpRequest::create('/client-order', 'GET', $query);
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_orders.get',
        title: 'Dettaglio ordine cliente',
        description: 'Ottiene un ordine per ID.'
    )]
    public function get(int $id): array
    {
        $request = HttpRequest::create('/client-order/' . $id, 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_orders.create',
        title: 'Crea ordine cliente',
        description: 'Crea un nuovo ordine. Passare i campi come mappa (form params).'
    )]
    public function create(array $data): array
    {
        // Il controller legge $request->request->all(), inviare come form params
        $request = HttpRequest::create('/client-order', 'POST', $data);
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_orders.update',
        title: 'Aggiorna ordine cliente',
        description: 'Aggiorna ordine esistente (PUT). Passare i campi da aggiornare.'
    )]
    public function update(int $id, array $data): array
    {
        $request = HttpRequest::create('/client-order/' . $id, 'PUT', $data);
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_orders.delete',
        title: 'Elimina ordine cliente',
        description: 'Elimina un ordine esistente per ID.'
    )]
    public function delete(int $id): array
    {
        $request = HttpRequest::create('/client-order/' . $id, 'DELETE');
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'client_orders.close',
        title: 'Chiudi ordine cliente',
        description: 'Chiude manualmente un ordine (PATCH /client-order/{id}/close)'
    )]
    public function close(int $id): array
    {
        $request = HttpRequest::create('/client-order/' . $id . '/close', 'PATCH');
        $request->headers->set('Authorization', 'Bearer ' . $this->tokens->getToken());
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}
