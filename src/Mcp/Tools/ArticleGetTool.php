<?php

namespace App\Mcp\Tools;

use App\Entity\Article;
use App\Mcp\McpToolInterface;
use App\Repository\ArticleRepository;

class ArticleGetTool implements McpToolInterface
{
    public function __construct(private readonly ArticleRepository $articles) {}

    public function name(): string { return 'article.get_by_id'; }

    public function description(): string
    {
        return 'Recupera un articolo per ID e restituisce i campi principali.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id'],
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'ID articolo'],
            ],
        ];
    }

    public function outputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'code' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'client_code' => ['type' => 'string'],
                'type' => ['type' => 'string'],
                'thickness' => ['type' => 'string'],
                'color' => ['type' => 'string'],
                'client' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }

    public function run(array $args): array
    {
        $id = (int)($args['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('Parametro id mancante o non valido');
        }
        $article = $this->articles->find($id);
        if (!$article instanceof Article) {
            throw new \RuntimeException('Articolo non trovato');
        }
        return $this->normalize($article);
    }

    /**
     * @return array<string,mixed>
     */
    private function normalize(Article $a): array
    {
        return [
            'id' => $a->getId(),
            'code' => $a->getCode(),
            'name' => $a->getName(),
            'client_code' => $a->getClientCode(),
            'type' => $a->getArticleType()?->getName() ?? null,
            'thickness' => $a->getThickness()?->getName() ?? null,
            'color' => $a->getColor()?->getName() ?? null,
            'client' => ($a->getClient()) ? [
                'id' => $a->getClient()?->getId(),
                'name' => $a->getClient()?->getName(),
            ] : null,
        ];
    }
}
