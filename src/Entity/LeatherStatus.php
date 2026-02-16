<?php

namespace App\Entity;

use App\Repository\LeatherStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeatherStatusRepository::class)]
class LeatherStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'leatherStatuses')]
    private ?MeasurementUnit $measurement_unit = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?float $flower_yield_coefficient = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMeasurementUnit(): ?MeasurementUnit
    {
        return $this->measurement_unit;
    }

    public function setMeasurementUnit(?MeasurementUnit $measurement_unit): static
    {
        $this->measurement_unit = $measurement_unit;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getFlowerYieldCoefficient(): ?float
    {
        return $this->flower_yield_coefficient;
    }

    public function setFlowerYieldCoefficient(?float $flower_yield_coefficient): static
    {
        $this->flower_yield_coefficient = $flower_yield_coefficient;

        return $this;
    }
}
