<?php

namespace App\Entity;

use App\Repository\SeaPortRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeaPortRepository::class)]
class SeaPort
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(nullable: true)]
    private ?int $deductible_day = null;

    #[ORM\Column(nullable: true)]
    private ?float $parking_day_cost = null;

    #[ORM\Column(nullable: true)]
    private ?int $container_deductible_day = null;

    #[ORM\Column(nullable: true)]
    private ?float $container_parking_day_cost = null;

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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDeductibleDay(): ?int
    {
        return $this->deductible_day;
    }

    public function setDeductibleDay(?int $deductible_day): static
    {
        $this->deductible_day = $deductible_day;

        return $this;
    }

    public function getParkingDayCost(): ?float
    {
        return $this->parking_day_cost;
    }

    public function setParkingDayCost(?float $parking_day_cost): static
    {
        $this->parking_day_cost = $parking_day_cost;

        return $this;
    }

    public function getContainerDeductibleDay(): ?int
    {
        return $this->container_deductible_day;
    }

    public function setContainerDeductibleDay(?int $container_deductible_day): static
    {
        $this->container_deductible_day = $container_deductible_day;

        return $this;
    }

    public function getContainerParkingDayCost(): ?float
    {
        return $this->container_parking_day_cost;
    }

    public function setContainerParkingDayCost(?float $container_parking_day_cost): static
    {
        $this->container_parking_day_cost = $container_parking_day_cost;

        return $this;
    }
}
