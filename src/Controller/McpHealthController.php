<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class McpHealthController
{
    #[Route(path: '/_mcp', name: 'mcp_health', methods: ['GET', 'HEAD', 'OPTIONS'])]
    public function __invoke(): Response
    {
        // Health/handshake endpoint for environments or clients that probe with GET/HEAD/OPTIONS.
        // Real MCP traffic must use POST on the same path and will be handled by the MCP bundle.
        return new Response('MCP OK', 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
