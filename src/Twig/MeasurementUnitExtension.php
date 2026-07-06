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
    public function toMq(float $quantity, ?MeasurementUnit $unit = null): float
    {
        if (!$unit) {
            return $quantity;
        }

        $prefix = $unit->getPrefix();
        if ($prefix === 'MQ') {
            return $quantity;
        }

        // Cerca coefficiente diretto: start = $unit, end = UM con prefix MQ
        foreach ($unit->getMeasurementUnitCoefficients() as $coeff) {
            $end = $coeff->getEndUm();
            if ($end && $end->getPrefix() === 'MQ' && $coeff->getCoefficient() > 0) {
                return $quantity * (float) $coeff->getCoefficient();
            }
        }

        // Fallback comune: da PQ a MQ dividendo per 10.764
        if ($prefix === 'PQ') {
            return $quantity / 10.764;
        }

        // Ultimo fallback: usa il primo coefficiente disponibile
        $first = $unit->getMeasurementUnitCoefficients()->first();
        if ($first && method_exists($first, 'getCoefficient') && $first->getCoefficient() > 0) {
            return $quantity * (float) $first->getCoefficient();
        }

        return $quantity;
    }
}
