<?php

namespace App\Mcp\Tools;

use App\Entity\Contact;
use App\Entity\ContactAddress;
use App\Entity\ContactDetail;
use App\Mcp\McpToolInterface;
use App\Repository\ContactRepository;

class ContactGetTool implements McpToolInterface
{
    public function __construct(private readonly ContactRepository $contacts) {}

    public function name(): string { return 'contact.get_by_id'; }

    public function description(): string
    {
        return 'Recupera un contatto per ID e restituisce i campi principali (nome, tipo, recapiti, indirizzo predefinito).';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id'],
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'ID contatto'],
            ],
        ];
    }

    public function outputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'type' => ['type' => 'string'],
                'details' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                        ],
                    ],
                ],
                'default_address' => [
                    'type' => 'object',
                    'properties' => [
                        'address' => ['type' => 'string'],
                        'zip_code' => ['type' => 'string'],
                        'nation' => ['type' => 'string'],
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
        $contact = $this->contacts->find($id);
        if (!$contact instanceof Contact) {
            throw new \RuntimeException('Contatto non trovato');
        }
        return $this->normalize($contact);
    }

    /**
     * @return array<string,mixed>
     */
    private function normalize(Contact $c): array
    {
        // Recapiti sintetici (max 3)
        $details = [];
        foreach ($c->getContactDetails() as $d) {
            if (!$d instanceof ContactDetail) { continue; }
            $details[] = [
                'type' => $d->getDetailType()?->getName() ?? null,
                'value' => $d->getName(),
            ];
            if (count($details) >= 3) { break; }
        }

        // Indirizzo predefinito (se esiste)
        $defaultAddress = null;
        foreach ($c->getContactAddresses() as $addr) {
            if ($addr instanceof ContactAddress && $addr->isDefaultAddress()) {
                $defaultAddress = [
                    'address' => trim(($addr->getAddress() ?? '').' '.($addr->getAddress2() ?? '').' '.($addr->getAddress3() ?? '')) ?: null,
                    'zip_code' => $addr->getZipCode(),
                    'nation' => $addr->getNation()?->getName() ?? null,
                ];
                break;
            }
        }

        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'type' => $c->getContactType()?->getName() ?? null,
            'details' => $details,
            'default_address' => $defaultAddress,
        ];
    }
}
