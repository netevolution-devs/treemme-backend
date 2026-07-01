<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class DdtRowTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'ddt_rows.list',
        title: 'Elenca righe DDT',
        description: 'Lista righe DDT. Usa endpoint /ddt-row senza ID.'
    )]
    public function list(): array
    {
        $request = HttpRequest::create('/ddt-row', 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt_rows.get',
        title: 'Dettaglio riga DDT',
        description: 'Ottiene il dettaglio di una riga DDT per ID.'
    )]
    public function get(int $id): array
    {
        $request = HttpRequest::create('/ddt-row/' . $id, 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt_rows.create',
        title: 'Crea riga DDT',
        description: 'Crea una nuova riga DDT (form params o JSON).'
    )]
    public function create(array $data): array
    {
        $request = HttpRequest::create('/ddt-row', 'POST', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt_rows.update',
        title: 'Aggiorna riga DDT',
        description: 'Aggiorna una riga DDT esistente (PUT).'
    )]
    public function update(int $id, array $data): array
    {
        $request = HttpRequest::create('/ddt-row/' . $id, 'PUT', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'ddt_rows.delete',
        title: 'Elimina riga DDT',
        description: 'Elimina una riga DDT per ID.'
    )]
    public function delete(int $id): array
    {
        $request = HttpRequest::create('/ddt-row/' . $id, 'DELETE');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}