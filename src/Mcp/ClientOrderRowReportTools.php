<?php

namespace App\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ClientOrderRowReportTools
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
    ) {}

    #[McpTool(
        name: 'client_order_rows.report',
        title: 'Ricerca righe ordine (report con filtri)',
        description: 'Interroga il report delle righe ordine con filtri opzionali: start_date (YYYY-MM-DD), end_date (YYYY-MM-DD), shipping_status (to_ship|shipped), production_status (to_produce|produced), print_status (to_print|printed), client_id (int). Usa endpoint GET /client-order-row-report.'
    )]
    public function report(
        ?string $start_date = null,
        ?string $end_date = null,
        ?string $shipping_status = null,
        ?string $production_status = null,
        ?string $print_status = null,
        ?int $client_id = null,
    ): array {
        $query = [];
        if ($start_date !== null && $start_date !== '') {
            $query['start_date'] = $start_date;
        }
        if ($end_date !== null && $end_date !== '') {
            $query['end_date'] = $end_date;
        }
        if ($shipping_status !== null && $shipping_status !== '') {
            $query['shipping_status'] = $shipping_status;
        }
        if ($production_status !== null && $production_status !== '') {
            $query['production_status'] = $production_status;
        }
        if ($print_status !== null && $print_status !== '') {
            $query['print_status'] = $print_status;
        }
        if ($client_id !== null) {
            $query['client_id'] = $client_id;
        }

        $request = HttpRequest::create('/client-order-row-report', 'GET', $query);
        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
        return json_decode($response->getContent() ?: '[]', true) ?? [];
    }
}
