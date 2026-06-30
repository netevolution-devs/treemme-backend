<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ArticleTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'articles.list',
        title: 'Elenca articoli',
        description: 'Restituisce la lista degli articoli. Filtro opzionale: client (id cliente).'
    )]
    public function list(?int $client = null): array
    {
        $query = [];
        if (null !== $client) {
            $query['client'] = $client;
        }
        $request = HttpRequest::create('/article', 'GET', $query);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'articles.get',
        title: 'Dettaglio articolo',
        description: 'Ottiene il dettaglio di un articolo per ID.'
    )]
    public function get(int $id): array
    {
        $request = HttpRequest::create('/article/' . $id, 'GET');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'articles.create',
        title: 'Crea articolo',
        description: 'Crea un nuovo articolo. Passare i campi nel payload (array associativo).'
    )]
    public function create(array $data): array
    {
        // ArticleController supporta JSON o form-data
        $request = HttpRequest::create('/article', 'POST', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'articles.update',
        title: 'Aggiorna articolo',
        description: "Aggiorna un articolo esistente tramite ID. Passare i campi nel payload."
    )]
    public function update(int $id, array $data): array
    {
        $request = HttpRequest::create('/article/' . $id, 'PUT', [], [], [], [], json_encode($data));
        $request->headers->set('Content-Type', 'application/json');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'articles.delete',
        title: 'Elimina articolo',
        description: 'Elimina un articolo esistente per ID.'
    )]
    public function delete(int $id): array
    {
        $request = HttpRequest::create('/article/' . $id, 'DELETE');
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}
