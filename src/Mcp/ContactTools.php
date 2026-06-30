<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Strumenti MCP per interagire con le rotte dei Contatti esistenti.
 * Ogni tool effettua una sub-request verso i controller già presenti,
 * così da riutilizzare la logica e le policy di sicurezza esistenti.
 */
final class ContactTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {
    }

    #[McpTool(
        name: 'contacts.list',
        title: 'Elenca contatti',
        description: 'Restituisce la lista dei contatti. Filtri opzionali: type, contact_name, detail_name.'
    )]
    public function listContacts(?string $type = null, ?string $contact_name = null, ?string $detail_name = null): array
    {
        $query = array_filter([
            'type' => $type,
            'contact_name' => $contact_name,
            'detail_name' => $detail_name,
        ], static fn($v) => null !== $v && $v !== '');

        $request = HttpRequest::create('/contact', 'GET', $query);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        return json_decode($response->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
    }

    #[McpTool(
        name: 'contacts.get',
        title: 'Dettaglio contatto',
        description: 'Ottiene il dettaglio di un contatto tramite ID.'
    )]
    public function getContact(int $id): array
    {
        $request = HttpRequest::create('/contact/' . $id, 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        return json_decode($response->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
    }

    #[McpTool(
        name: 'contacts.create',
        title: 'Crea contatto',
        description: 'Crea un nuovo contatto. Passare i campi del corpo richiesta come mappa chiave/valore.'
    )]
    public function createContact(array $data): array
    {
        $request = HttpRequest::create('/contact', 'POST', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        return json_decode($response->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
    }

    #[McpTool(
        name: 'contacts.update',
        title: 'Aggiorna contatto',
        description: 'Aggiorna un contatto esistente tramite ID. Passare i campi da aggiornare.'
    )]
    public function updateContact(int $id, array $data): array
    {
        // Nota: il controller usa PUT per l’aggiornamento
        $request = HttpRequest::create('/contact/' . $id, 'PUT', [], [], [], [], json_encode($data, JSON_UNESCAPED_UNICODE));
        $request->headers->set('Content-Type', 'application/json');

        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        return json_decode($response->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
    }

    #[McpTool(
        name: 'contacts.delete',
        title: 'Elimina contatto',
        description: 'Elimina un contatto esistente tramite ID.'
    )]
    public function deleteContact(int $id): array
    {
        $request = HttpRequest::create('/contact/' . $id, 'DELETE');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        return json_decode($response->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
    }
}
