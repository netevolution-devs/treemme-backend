<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolInterface;

class PingTool implements McpToolInterface
{
    public function name(): string
    {
        return 'ping';
    }

    public function description(): string
    {
        return 'Simple health check returning current server time.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'echo' => ['type' => 'string', 'description' => 'Optional message to echo back'],
            ],
        ];
    }

    public function outputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'pong' => ['type' => 'boolean'],
                'time' => ['type' => 'string'],
                'echo' => ['type' => 'string'],
            ],
        ];
    }

    public function run(array $args): array
    {
        return [
            'pong' => true,
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'echo' => $args['echo'] ?? null,
        ];
    }
}
