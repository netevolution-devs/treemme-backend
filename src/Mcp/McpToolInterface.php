<?php

namespace App\Mcp;

interface McpToolInterface
{
    /** Unique tool name (machine-friendly) */
    public function name(): string;

    /** Human description */
    public function description(): string;

    /** JSON-Schema-like array describing expected input */
    public function inputSchema(): array;

    /** JSON-Schema-like array describing output */
    public function outputSchema(): array;

    /** Execute tool */
    public function run(array $args): array;
}
