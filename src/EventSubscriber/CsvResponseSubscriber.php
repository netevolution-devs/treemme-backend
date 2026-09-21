<?php

namespace App\EventSubscriber;

use App\Csv\CsvMappingRegistry;
use App\Csv\AutoCsvMapper;
use App\Service\CsvExportService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -8)]
final class CsvResponseSubscriber
{
    public function __construct(
        private CsvMappingRegistry $registry,
        private CsvExportService $export,
        private AutoCsvMapper $auto
    ) {}

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Attiva solo con ?export=csv (o true/1)
        $exportParam = $request->query->get('export');
        $wantsCsv = $exportParam === 'csv' || $exportParam === '1' || $exportParam === 'true';
        if (!$wantsCsv) {
            return;
        }

        $route = (string) $request->attributes->get('_route');

        $response = $event->getResponse();
        if (!$response instanceof JsonResponse) {
            // Se necessario, si può aggiungere un listener su VIEW per gestire anche array/DTO grezzi
            return;
        }

        $payload = json_decode($response->getContent(), true);

        if ($route && $this->registry->has($route) && $request->query->get('auto') !== '1') {
            // Mapping esplicito registrato (a meno che non sia forzato auto=1)
            [$data, $columns, $filename] = $this->registry->resolve($route, $request, $payload);
        } else {
            // Fallback auto‑mapping
            [$data, $columns, $filename] = $this->auto->autoMap($request, $payload);
        }
        $csv = $this->export->stream($data, $columns, $filename, ['delimiter' => ';', 'bom' => 'utf-8-sig']);

        // Preserva status code e header (CORS, Cache, ecc.) della risposta originale
        $csv->setStatusCode($response->getStatusCode());

        // Copia tutti gli header esistenti, senza sovrascrivere Content-Type/Disposition impostati per il CSV
        $originalHeaders = $response->headers->allPreserveCaseWithoutCookies();
        foreach ($originalHeaders as $name => $values) {
            $lname = strtolower($name);
            if (in_array($lname, ['content-type', 'content-disposition'])) {
                continue;
            }
            foreach ($values as $v) {
                $csv->headers->set($name, $v, false);
            }
        }
        // Copia eventuali cookie
        foreach ($response->headers->getCookies() as $cookie) {
            $csv->headers->setCookie($cookie);
        }

        $event->setResponse($csv);
    }
}
