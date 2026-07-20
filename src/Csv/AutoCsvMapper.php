<?php

namespace App\Csv;

use Symfony\Component\HttpFoundation\Request;

/**
 * Generatore di mapping CSV automatico con flatten dell'output JSON.
 */
final class AutoCsvMapper
{
    /**
     * Restituisce tuple [data, columns, filename] per l'auto‑mapping.
     * - Rileva automaticamente il data root (payload['data'] se presente)
     * - Appiattisce oggetti/array in chiavi dot‑notation
     * - Genera header leggibili
     *
     * @return array{0: iterable, 1: array<string,string>, 2: string}
     */
    public function autoMap(Request $request, mixed $payload): array
    {
        $root = is_array($payload) ? ($payload['data'] ?? $payload) : $payload;

        // Normalizza in lista di righe
        if ($this->isList($root)) {
            $rows = $root;
        } else {
            $rows = [$root];
        }

        // Flatten per ogni riga
        $flatRows = [];
        foreach ($rows as $row) {
            $flatRows[] = $this->flatten($row, '', 0, 3);
        }

        // Colonne: usa le chiavi del primo elemento
        $first = $flatRows[0] ?? [];
        $columns = [];
        foreach (array_keys($first) as $key) {
            $columns[$this->humanizeHeader($key)] = $key; // path = chiave flat
        }

        if (empty($columns)) {
            $columns = ['Valore' => 'value'];
            $flatRows = array_map(fn($v) => ['value' => is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)], $rows);
        }

        $route = (string) $request->attributes->get('_route');
        $filename = ($route ? str_replace(['.', ':'], '_', $route) : 'export') . '.csv';

        return [$flatRows, $columns, $filename];
    }

    private function isList($value): bool
    {
        if (!is_array($value)) return false;
        if ($value === []) return false; // consideriamo vuoto come non-list per evitare ambiguità
        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * Flatten ricorsivo con depth limit. Gestione array di scalari con join, array di oggetti con JSON.
     * @return array<string, scalar|null>
     */
    private function flatten($value, string $prefix, int $depth, int $maxDepth): array
    {
        $out = [];
        if ($depth > $maxDepth) {
            $out[rtrim($prefix, '.')] = is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            return $out;
        }

        if (is_array($value)) {
            if ($this->isList($value)) {
                // Lista: se scalari → join, altrimenti JSON compatto
                if ($this->isListOfScalars($value)) {
                    $out[rtrim($prefix, '.')] = implode(', ', array_map(fn($v) => (string) $v, $value));
                } else {
                    $out[rtrim($prefix, '.')] = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            } else {
                foreach ($value as $k => $v) {
                    $key = $prefix . (string)$k;
                    $out += $this->flatten($v, $key . '.', $depth + 1, $maxDepth);
                }
            }
        } elseif (is_object($value)) {
            // Per oggetti generici serializziamo a JSON al primo livello utile
            $out[rtrim($prefix, '.')] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            // scalare o null
            $out[rtrim($prefix, '.')] = $value;
        }

        return $out;
    }

    private function isListOfScalars(array $arr): bool
    {
        foreach ($arr as $v) {
            if (!(is_null($v) || is_scalar($v))) return false;
        }
        return true;
    }

    private function humanizeHeader(string $dotKey): string
    {
        $parts = explode('.', $dotKey);
        $parts = array_map(function ($p) {
            // snake/camel → Title Case
            $p = preg_replace('/(?<!^)[A-Z]/', ' $0', (string)$p);
            $p = str_replace('_', ' ', (string)$p);
            return ucwords(trim((string)$p));
        }, $parts);
        return implode(' · ', $parts);
    }
}
