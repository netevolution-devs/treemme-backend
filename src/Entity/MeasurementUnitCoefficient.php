<?php

namespace App\Entity;

use App\Repository\MeasurementUnitCoefficientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeasurementUnitCoefficientRepository::class)]
class MeasurementUnitCoefficient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'MeasurementUnitCoefficients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MeasurementUnit $start_um = null;

    #[ORM\ManyToOne(inversedBy: 'EndMeasurementUnitCoefficients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MeasurementUnit $end_um = null;

    #[ORM\Column]
    private ?float $coefficient = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartUm(): ?MeasurementUnit
    {
        return $this->start_um;
    }

    public function setStartUm(?MeasurementUnit $start_um): static
    {
        $this->start_um = $start_um;

        return $this;
    }

    public function getEndUm(): ?MeasurementUnit
    {
        return $this->end_um;
    }

    public function setEndUm(?MeasurementUnit $end_um): static
    {
        $this->end_um = $end_um;

        return $this;
    }

    public function getCoefficient(): ?float
    {
        return $this->coefficient;
    }

    public function setCoefficient(float $coefficient): static
    {
        $this->coefficient = $coefficient;

        return $this;
    }
}
