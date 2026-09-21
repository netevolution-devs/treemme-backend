<?php

namespace App\Csv;

use Symfony\Component\HttpFoundation\Request;

final class CsvMappingRegistry
{
    /** @var array<string, callable(Request $r, mixed $payload): array{0: iterable, 1: array, 2: string}> */
    private array $mappings = [];

    public function register(string $routeName, callable $resolver): void
    {
        $this->mappings[$routeName] = $resolver;
    }

    public function has(string $routeName): bool
    {
        return isset($this->mappings[$routeName]);
    }

    /**
     * @return array{0: iterable, 1: array, 2: string} [data, columns, filename]
     */
    public function resolve(string $routeName, Request $request, mixed $payload): array
    {
        return ($this->mappings[$routeName])($request, $payload);
    }
}
