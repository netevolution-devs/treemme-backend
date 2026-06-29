<?php

namespace App\Mcp\Tools;

use App\Entity\Contact;
use App\Mcp\McpToolInterface;
use App\Repository\ContactRepository;

class ContactSearchTool implements McpToolInterface
{
    public function __construct(private readonly ContactRepository $contacts) {}

    public function name(): string { return 'contact.search'; }

    public function description(): string
    {
        return 'Ricerca contatti per testo libero su nome e dettagli di contatto (es. email/telefono).';
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
                            'name' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
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

        $qb = $this->contacts->createQueryBuilder('c');
        $qb->leftJoin('c.contactDetails', 'cd')
            ->where($qb->expr()->orX(
                $qb->expr()->like('LOWER(c.name)', ':q'),
                $qb->expr()->like('LOWER(cd.name)', ':q')
            ))
            ->setParameter('q', '%'.mb_strtolower($q).'%')
            ->setMaxResults($limit)
            ->orderBy('c.name', 'ASC');

        /** @var Contact[] $results */
        $results = $qb->getQuery()->getResult();

        $items = array_map(function (Contact $c) {
            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'type' => $c->getContactType()?->getName() ?? null,
            ];
        }, $results);

        return [
            'items' => $items,
            'count' => count($items),
        ];
    }
}
