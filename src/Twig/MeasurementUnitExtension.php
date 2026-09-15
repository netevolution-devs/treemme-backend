<?php

namespace App\Twig;

use App\Entity\MeasurementUnit;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MeasurementUnitExtension extends AbstractExtension
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('to_mq', [$this, 'toMq']),
        ];
    }

    /**
     * Converte una quantità dalla sua unità di misura all'unità MQ.
     * - Se l'UM è già MQ, restituisce la quantità invariata.
     * - Se esiste un coefficiente diretto verso MQ usa quello.
     * - Se l'UM è PQ e non esiste coefficiente esplicito, usa 1 MQ = 10.764 PQ.
     * - Altrimenti tenta il primo coefficiente disponibile come fallback.
     */
    public function toMq(mixed $quantity, mixed $unit = null): float
    {
        $qty = (float) $quantity;

        if (!$unit) {
            return $qty;
        }

        $unitEntity = null;
        if ($unit instanceof MeasurementUnit) {
            $unitEntity = $unit;
        } elseif (is_array($unit)) {
            if (isset($unit['id'])) {
                $unitEntity = $this->em->getRepository(MeasurementUnit::class)->find($unit['id']);
            }
        } elseif (is_numeric($unit)) {
            $unitEntity = $this->em->getRepository(MeasurementUnit::class)->find((int) $unit);
        }

        if ($unitEntity instanceof MeasurementUnit) {
            $prefix = $unitEntity->getPrefix();
            if ($prefix === 'MQ') {
                return $qty;
            }

            // Cerca coefficiente diretto: start = $unitEntity, end = UM con prefix MQ
            foreach ($unitEntity->getMeasurementUnitCoefficients() as $coeff) {
                $end = $coeff->getEndUm();
                if ($end && $end->getPrefix() === 'MQ' && $coeff->getCoefficient() > 0) {
                    return $qty * (float) $coeff->getCoefficient();
                }
            }

            // Fallback comune: da PQ a MQ dividendo per 10.764
            if ($prefix === 'PQ') {
                return $qty / 10.764;
            }

            // Ultimo fallback: usa il primo coefficiente disponibile
            $first = $unitEntity->getMeasurementUnitCoefficients()->first();
            if ($first && method_exists($first, 'getCoefficient') && $first->getCoefficient() > 0) {
                return $qty * (float) $first->getCoefficient();
            }

            return $qty;
        }

        // Se non abbiamo trovato l'entità, proviamo a usare i dati presenti nell'array se disponibili
        if (is_array($unit) && isset($unit['prefix'])) {
            if ($unit['prefix'] === 'MQ') {
                return $qty;
            }
            if ($unit['prefix'] === 'PQ') {
                return $qty / 10.764;
            }
        }

        return $qty;
    }
}
