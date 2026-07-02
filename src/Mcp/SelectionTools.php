<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SelectionTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'selections.stock.available',
        title: 'Disponibilità selezioni a magazzino',
        description: 'Elenca selezioni con pezzi disponibili o, se fornito un ID, i lotti disponibili per quella selezione. Usa endpoint /selection/stock/available/{id?}.'
    )]
    public function available(?int $id = null): array
    {
        $path = '/selection/stock/available' . ($id !== null ? '/' . $id : '');
        $request = HttpRequest::create($path, 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        $contentType = $response->headers->get('Content-Type');
        if ($contentType && str_starts_with($contentType, 'application/json')) {
            return json_decode($response->getContent() ?: '[]', true) ?? [];
        }
        return [];
    }
}
