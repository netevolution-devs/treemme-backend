<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class BatchTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'batches.production.create_dye',
        title: 'Metti in produzione (Tintura)',
        description: 'Crea un lotto di Tintura (TF) da una riga ordine con quantità, data programmata e macchinario. Endpoint: POST /batch/dye. Parametri richiesti: client_order_row_id (int), quantity (float), scheduled_date (YYYY-MM-DD), machine_id (int).'
    )]
    public function createDye(int $client_order_row_id, float $quantity, string $scheduled_date, int $machine_id): array
    {
        $data = [
            'client_order_row_id' => $client_order_row_id,
            'quantity' => $quantity,
            'scheduled_date' => $scheduled_date,
            'machine_id' => $machine_id,
        ];
        $request = HttpRequest::create('/batch/dye', 'POST', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }

    #[McpTool(
        name: 'batches.production.create_refinement',
        title: 'Metti in produzione (Rifinizione)',
        description: 'Crea un lotto di Rifinizione (UF) da una riga ordine con quantità. Endpoint: POST /batch/refinement. Parametri richiesti: client_order_row_id (int), quantity (float).'
    )]
    public function createRefinement(int $client_order_row_id, float $quantity): array
    {
        $data = [
            'client_order_row_id' => $client_order_row_id,
            'quantity' => $quantity,
        ];
        $request = HttpRequest::create('/batch/refinement', 'POST', $data);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}
