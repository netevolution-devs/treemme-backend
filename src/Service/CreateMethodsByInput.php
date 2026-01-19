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
        foreach ($data as $field => $value) {
            $method = 'set' . str_replace('_', '', ucwords($field, '_'));
            if (!method_exists($entity, $method)) {
                throw new \RuntimeException($field . ' non trovato');
            }

            if (is_string($value) && (
                    DateTime::createFromFormat('Y-m-d', substr($value, 0, 10)) !== false
                    || DateTime::createFromFormat('d/m/Y', substr($value, 0, 10)) !== false
                    || DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false
                    || DateTime::createFromFormat('H:i', substr($value, 0, 5)) !== false
                    || DateTime::createFromFormat('H:i:s', substr($value, 0, 8)) !== false
                )) {
                if ($dateFromFormat = DateTime::createFromFormat('d/m/Y', substr($value, 0, 10))) {
                    [$day, $month, $year] = explode('/', substr($value, 0, 10));
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
                    } else if ($expectedType === 'DateTimeInterface') {
                        // DateTimeInterface richiede un'implementazione concreta
                        $value = new \DateTime($value);
                    }
                } catch (\Exception $e) {
                    throw new \RuntimeException('Errore in inserimento data: ' . $e->getMessage());
                }

            } else {
                $value_to_number = str_replace(',', '.', $value);
                $value = is_numeric($value_to_number) ? (float)$value_to_number : $value;
            }

            $entity->{$method}($value);
        }

        return $entity;
    }

}
