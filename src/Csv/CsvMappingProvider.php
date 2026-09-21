<?php

namespace App\Csv;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class CsvMappingProvider
{
    private bool $initialized = false;

    public function __construct(private CsvMappingRegistry $registry) {}

    /**
     * Inizializza i mapping una sola volta al primo request.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $this->init();
    }

    public function init(): void
    {
        // Mapping per la rotta del report lotti
        $this->registry->register('get_batch_report', function (Request $r, $payload) {
            // Il payload atteso è il JSON completo di DoResponseService: ['data' => reportData, ...]
            $dataRoot = is_array($payload) ? ($payload['data'] ?? []) : [];

            $section = $r->query->get('section', 'summary');
            $filename = 'batch_report_' . $section . '.csv';

            if ($section === 'costs') {
                $data = $dataRoot['costs'] ?? [];
                $columns = [
                    'Tipo costo' => 'type',
                    'Descrizione' => 'description',
                    'Importo' => 'amount',
                    'Valuta' => 'currency',
                ];
                return [$data, $columns, $filename];
            }

            if ($section === 'sales') {
                $data = $dataRoot['sales'] ?? [];
                $columns = [
                    'Documento' => 'document_number',
                    'Cliente' => 'client_name',
                    'Articolo' => 'article_code',
                    'Q.tà' => 'quantity',
                    'UM' => 'um',
                    'Prezzo' => 'price',
                    'Totale' => 'total',
                ];
                return [$data, $columns, $filename];
            }

            // default: summary (una riga con i totali del report)
            $report = $dataRoot['report'] ?? [];
            $data = [$report];
            $columns = [
                'Lotto' => '../code', // risale di un livello se presente dataRoot['code']
                'Pezzi venduti' => 'sold_pieces',
                'Q.tà venduta' => 'sold_quantity',
                'Q.tà venduta (PQ)' => 'sold_quantity_ftsq',
                'Pezzi disponibili' => 'available_pieces',
                'Q.tà disponibile' => 'available_quantity',
                'Q.tà disponibile (PQ)' => 'available_quantity_ftsq',
                'Ricavo totale' => 'total_revenue',
                'Prezzo vendita medio' => 'total_sale_price',
                'Costi totali' => 'total_costs',
                'Comp. sfrido' => 'compensation_waste',
            ];

            // Adapter per leggere '../code' rispetto a $report in dataRoot
            $columns = array_map(function ($extractor) use ($dataRoot) {
                if (is_string($extractor) && str_starts_with($extractor, '../')) {
                    $path = substr($extractor, 3);
                    return function ($row) use ($dataRoot, $path) {
                        $ref = $dataRoot;
                        foreach (explode('.', $path) as $seg) {
                            if (!is_array($ref)) return null;
                            $ref = $ref[$seg] ?? null;
                            if ($ref === null) break;
                        }
                        return $ref;
                    };
                }
                return $extractor;
            }, $columns);

            return [$data, $columns, $filename];
        });

        // Mapping per disponibilità selezioni
        $this->registry->register('get_selection_stock_available', function (Request $r, $payload) {
            // Payload standard DoResponseService: ['data' => [...]]
            $data = is_array($payload) ? ($payload['data'] ?? []) : [];

            $id = $r->attributes->get('id');
            $filename = $id ? ('selection_' . $id . '_available.csv') : 'selection_available.csv';

            // Caso elenco selezioni senza id: struttura come nell'esempio utente
            if (!$id) {
                $columns = [
                    'ID' => 'id',
                    'Nome' => 'name',
                    'Pezzi disponibili' => 'available_pieces',
                ];
                return [$data, $columns, $filename];
            }

            // Caso con id: elenco di batch della selezione con stock > 0.
            // Poiché la struttura può variare in base alla serialization group 'batch_list',
            // generiamo dinamicamente le colonne dalle chiavi del primo elemento.
            $columns = [];
            $first = null;
            foreach ($data as $row) { $first = $row; break; }
            if (is_array($first)) {
                foreach (array_keys($first) as $key) {
                    // Header leggibile: Title Case da snake/camel
                    $header = ucwords(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', str_replace('_', ' ', (string)$key))));
                    $columns[$header] = (string)$key; // path identico alla chiave
                }
            }

            // Fallback minimale se vuoto
            if (empty($columns)) {
                $columns = [
                    'ID' => 'id',
                ];
            }

            return [$data, $columns, $filename];
        });
    }
}
