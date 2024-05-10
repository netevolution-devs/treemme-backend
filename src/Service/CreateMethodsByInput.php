<?php

namespace App\Service;

use DateTime;
use Exception;

class CreateMethodsByInput
{
    /**
     * set attribute and value of given data to given entity
     *
     * @throws Exception
     */
    public function createMethods($entity, array $data): object|string
    {
        if (isset($data['lot_nr'])) {
            $data['lot'] = $data['lot_nr'];
            unset($data['lot_nr']);
        }
        foreach ($data as $field => $value) {
            $method = 'set' . str_replace('_', '', ucwords($field, '_'));
            if (!method_exists($entity, $method)) {
                throw new \RuntimeException($field . ' non trovato');
            }

            if (DateTime::createFromFormat('Y-m-d', substr($value, 0, 10))
                || DateTime::createFromFormat('d/m/Y', substr($value, 0, 10))
                || DateTime::createFromFormat('H:i', substr($value, 0, 10))
                || DateTime::createFromFormat('H:i:s', substr($value, 0, 10))
            ) {
                if ($dateFromFormat = DateTime::createFromFormat('d/m/Y', substr($value, 0, 10))) {
                    [$day, $month, $year] = explode('/', $value);
                    if (checkdate((int)$month, (int)$day, (int)$year)) {
                        $value = $dateFromFormat->format('Y-m-d');
                    } else {
                        throw new \RuntimeException('Formato data non valido');
                    }
                }
                try {
                    $reflectionMethod = new \ReflectionMethod($entity, $method);

                    $params = $reflectionMethod->getParameters();
                    $expectedType = $params[0]?->getType()?->getName();

                    if ($expectedType === 'DateTime') {
                        $value = new \DateTime($value);
                    } else if ($expectedType === 'DateTimeImmutable') {
                        $value = new \DateTimeImmutable($value);
                    }
                } catch (\Exception $e) {
                    throw new \RuntimeException('Errore in inserimento data');
                }

            } else {
                $value_to_number = str_replace(',', '.',$value);
                $value = is_numeric($value_to_number) ? (float)$value_to_number : $value;
            }

            $entity->{$method}($value);
        }

        return $entity;
    }

}