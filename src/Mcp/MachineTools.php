<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MachineTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'machines.list',
        title: 'Elenca macchinari',
        description: 'Restituisce la lista dei macchinari disponibili. Endpoint: GET /machine.'
    )]
    public function list(): array
    {
        $request = HttpRequest::create('/machine', 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'machines.get',
        title: 'Dettaglio macchinario',
        description: 'Ottiene i dettagli di un macchinario per ID. Endpoint: GET /machine/{id}.'
    )]
    public function get(int $id): array
    {
        $request = HttpRequest::create('/machine/' . $id, 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}
