<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ProductionTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'production.list',
        title: 'Elenca produzioni',
        description: 'Lista produzioni. Filtro opzionale: scheduled_date (YYYY-MM-DD). Usa endpoint GET /production.'
    )]
    public function list(?string $scheduled_date = null): array
    {
        $query = [];
        if ($scheduled_date !== null && $scheduled_date !== '') {
            $query['scheduled_date'] = $scheduled_date;
        }
        $request = HttpRequest::create('/production', 'GET', $query);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'production.get',
        title: 'Dettaglio produzione',
        description: 'Ottiene una produzione per ID. Usa endpoint GET /production/{id}.'
    )]
    public function get(int $id): array
    {
        $request = HttpRequest::create('/production/' . $id, 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'production.create',
        title: 'Crea produzione',
        description: 'Crea una nuova produzione. Campi attesi (JSON o form): batch_id, machine_id, scheduled_date (YYYY-MM-DD) e altri campi supportati.'
    )]
    public function create(array $data): array
    {
        // Il controller accetta JSON o form-data: inviamo come form params per coerenza con altri tools
        $request = HttpRequest::create('/production', 'POST', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'production.update',
        title: 'Aggiorna produzione',
        description: 'Aggiorna una produzione esistente (PUT /production/{id}).'
    )]
    public function update(int $id, array $data): array
    {
        // Usiamo JSON per gli update, conforme a quanto usato in altri tools
        $request = HttpRequest::create('/production/' . $id, 'PUT', [], [], [], [], json_encode($data));
        $request->headers->set('Content-Type', 'application/json');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'production.delete',
        title: 'Elimina produzione',
        description: 'Elimina una produzione per ID (DELETE /production/{id}).'
    )]
    public function delete(int $id): array
    {
        $request = HttpRequest::create('/production/' . $id, 'DELETE');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'production.by_batch',
        title: 'Produzioni per lotto',
        description: 'Elenca produzioni collegate ad un lotto specifico (GET /batch/{batch_id}/production).'
    )]
    public function byBatch(int $batch_id): array
    {
        $request = HttpRequest::create('/batch/' . $batch_id . '/production', 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}
