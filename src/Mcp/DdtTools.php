<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class DdtTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'ddt.list',
        title: 'Elenca DDT',
        description: 'Lista DDT con filtri opzionali: subcontractor_id, client_id, start_date (Y-m-d), end_date (Y-m-d).'
    )]
    public function list(?int $subcontractor_id = null, ?int $client_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        $query = array_filter([
            'subcontractor_id' => $subcontractor_id,
            'client_id' => $client_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ], static fn($v) => $v !== null && $v !== '');

        $request = HttpRequest::create('/ddt', 'GET', $query);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt.get',
        title: 'Dettaglio DDT',
        description: 'Ottiene il dettaglio di un DDT per ID.'
    )]
    public function get(int $id): array
    {
        $request = HttpRequest::create('/ddt/' . $id, 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt.create',
        title: 'Crea DDT',
        description: 'Crea un nuovo DDT. Accetta JSON o form params.'
    )]
    public function create(array $data): array
    {
        $request = HttpRequest::create('/ddt', 'POST', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt.update',
        title: 'Aggiorna DDT',
        description: 'Aggiorna un DDT esistente (PUT/PATCH).'
    )]
    public function update(int $id, array $data): array
    {
        // Usiamo PUT con JSON per massima compatibilità
        $request = HttpRequest::create('/ddt/' . $id, 'PUT', [], [], [], [], json_encode($data));
        $request->headers->set('Content-Type', 'application/json');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt.delete',
        title: 'Elimina DDT',
        description: 'Elimina un DDT per ID.'
    )]
    public function delete(int $id): array
    {
        $request = HttpRequest::create('/ddt/' . $id, 'DELETE');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}