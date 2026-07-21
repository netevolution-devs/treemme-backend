<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExportService
{
    /**
     * @param iterable $data  Collezione/iteratore di entity/array
     * @param array $columns  ['Header' => 'prop.path' | callable($row): mixed]
     * @param string $filename
     * @param array $options  ['delimiter' => ';', 'enclosure' => '"', 'escape' => "\\", 'bom' => 'utf-8-sig']
     */
    public function stream(iterable $data, array $columns, string $filename, array $options = []): StreamedResponse
    {
        $delimiter = $options['delimiter'] ?? ';';
        $enclosure = $options['enclosure'] ?? '"';
        $escape    = $options['escape']    ?? "\\";
        $bom       = $options['bom']       ?? 'utf-8-sig';

        $response = new StreamedResponse(function () use ($data, $columns, $delimiter, $enclosure, $escape, $bom) {
            $out = fopen('php://output', 'wb');
            if ($bom === 'utf-8-sig') {
                fwrite($out, "\xEF\xBB\xBF"); // BOM utile per Excel
            }
            // Header colonne
            fputcsv($out, array_keys($columns), $delimiter, $enclosure, $escape);

            foreach ($data as $row) {
                $line = [];
                foreach ($columns as $extractor) {
                    if (is_callable($extractor)) {
                        $value = $extractor($row);
                    } elseif (is_string($extractor)) {
                        $value = $this->readPropertyPath($row, $extractor);
                    } else {
                        $value = null;
                    }
                    $line[] = $this->toScalar($value);
                }
                fputcsv($out, $line, $delimiter, $enclosure, $escape);
            }
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    private function readPropertyPath($row, string $path)
    {
        foreach (explode('.', $path) as $segment) {
            if (is_array($row)) {
                $row = $row[$segment] ?? null;
            } else {
                $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $segment)));
                $isser  = 'is'  . str_replace(' ', '', ucwords(str_replace('_', ' ', $segment)));
                $row = method_exists($row, $getter) ? $row->$getter() : (method_exists($row, $isser) ? $row->$isser() : null);
            }
            if ($row === null) break;
        }
        return $row;
    }

    private function toScalar($value)
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_float($value) => number_format($value, 2, ',', ''),
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
