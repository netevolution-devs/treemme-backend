<?php

namespace App\Mcp;

use Psr\Log\LoggerInterface;

class McpServer
{
    /** @var array<string,McpToolInterface> */
    private array $tools = [];

    public function __construct(iterable $tools, private readonly LoggerInterface $logger)
    {
        foreach ($tools as $tool) {
            if ($tool instanceof McpToolInterface) {
                $this->tools[$tool->name()] = $tool;
            }
        }
    }

    public function handleRequestPublic(array $request): ?array
    {
        try {
            return $this->handleRequest($request);
        } catch (\Throwable $e) {
            $this->logger->error('MCP error: '.$e->getMessage(), ['exception' => $e]);
            return $this->errorResponse($request['id'] ?? null, 500, 'Internal MCP error');
        }
    }

    private function handleRequest(array $request): array
    {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? null;
        $params = $request['params'] ?? [];

        return match ($method) {
            'initialize' => $this->handleInitialize($id, $params),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($id, $params),
            default => $this->errorResponse($id, 400, 'Unknown method'),
        };
    }

    private function handleInitialize(mixed $id, array $params): array
    {
        $client = $params['client'] ?? [];
        $name = $client['name'] ?? 'unknown';
        $version = $client['version'] ?? 'unknown';
        $this->logger->info('MCP initialize from client', ['name' => $name, 'version' => $version]);
        return $this->successResponse($id, [
            'protocolVersion' => '2024-11-05',
            'serverInfo' => [
                'name' => 'tre_emme_backend',
                'version' => '1.0.0',
            ],
        ]);
    }

    private function handleToolsList(mixed $id): array
    {
        $list = [];
        foreach ($this->tools as $tool) {
            $list[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
                'outputSchema' => $tool->outputSchema(),
            ];
        }
        return $this->successResponse($id, [
            'tools' => $list,
        ]);
    }

    private function handleToolsCall(mixed $id, array $params): array
    {
        $name = $params['name'] ?? null;
        $args = $params['arguments'] ?? [];
        if (!$name || !isset($this->tools[$name])) {
            return $this->errorResponse($id, 404, 'Tool not found');
        }
        $result = $this->tools[$name]->run(is_array($args) ? $args : []);
        return $this->successResponse($id, [
            'content' => [[
                'type' => 'object',
                'data' => $result,
            ]],
        ]);
    }

    private function successResponse(mixed $id, array $result): array
    {
        return [
            'jsonrpc' => '2.0', // <-- Aggiunto
            'id' => $id,
            'result' => $result,
        ];
    }

    private function errorResponse(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0', // <-- Aggiunto
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'id' => $id,
        ];
    }
}
