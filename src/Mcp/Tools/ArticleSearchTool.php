<?php

namespace App\Mcp\Tools;

use App\Entity\Article;
use App\Mcp\McpToolInterface;
use App\Repository\ArticleRepository;

class ArticleSearchTool implements McpToolInterface
{
    public function __construct(private readonly ArticleRepository $articles) {}

    public function name(): string { return 'article.search'; }

    public function description(): string
    {
        return 'Ricerca articoli per testo libero su code/name/client_code. Restituisce una lista sintetica.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['query'],
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Testo da cercare (min 2 caratteri)'],
                'limit' => ['type' => 'integer', 'description' => 'Limite risultati (1..100), default 20'],
            ],
        ];
    }

    public function outputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'code' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'client_code' => ['type' => 'string'],
                        ],
                    ],
                ],
                'count' => ['type' => 'integer'],
            ],
        ];
    }

    public function run(array $args): array
    {
        $q = trim((string)($args['query'] ?? ''));
        if (mb_strlen($q) < 2) {
            throw new \InvalidArgumentException('query deve contenere almeno 2 caratteri');
        }
        $limit = (int)($args['limit'] ?? 20);
        $limit = max(1, min(100, $limit));

        $qb = $this->articles->createQueryBuilder('a');
        $qb->where($qb->expr()->orX(
                $qb->expr()->like('LOWER(a.code)', ':q'),
                $qb->expr()->like('LOWER(a.name)', ':q'),
                $qb->expr()->like('LOWER(a.client_code)', ':q')
            ))
            ->setParameter('q', '%'.mb_strtolower($q).'%')
            ->setMaxResults($limit)
            ->orderBy('a.name', 'ASC');

        /** @var Article[] $results */
        $results = $qb->getQuery()->getResult();

        $items = array_map(function (Article $a) {
            return [
                'id' => $a->getId(),
                'code' => $a->getCode(),
                'name' => $a->getName(),
                'client_code' => $a->getClientCode(),
            ];
        }, $results);

        return [
            'items' => $items,
            'count' => count($items),
        ];
    }
}
