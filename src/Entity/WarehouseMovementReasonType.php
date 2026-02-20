<?php

namespace App\Entity;

use App\Repository\WarehouseMovementReasonTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WarehouseMovementReasonTypeRepository::class)]
class WarehouseMovementReasonType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 5)]
    private ?string $movement_type = null;

    /**
     * @var Collection<int, WarehouseMovementReason>
     */
    #[ORM\OneToMany(mappedBy: 'reason_type', targetEntity: WarehouseMovementReason::class)]
    private Collection $warehouseMovementReasons;

    public function __construct()
    {
        $this->warehouseMovementReasons = new ArrayCollection();
    }

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

    public function getMovementType(): ?string
    {
        return $this->movement_type;
    }

    public function setMovementType(string $movement_type): static
    {
        $this->movement_type = $movement_type;

        return $this;
    }

    /**
     * @return Collection<int, WarehouseMovementReason>
     */
    public function getWarehouseMovementReasons(): Collection
    {
        return $this->warehouseMovementReasons;
    }

    public function addWarehouseMovementReason(WarehouseMovementReason $warehouseMovementReason): static
    {
        if (!$this->warehouseMovementReasons->contains($warehouseMovementReason)) {
            $this->warehouseMovementReasons->add($warehouseMovementReason);
            $warehouseMovementReason->setReasonType($this);
        }

        return $this;
    }

    public function removeWarehouseMovementReason(WarehouseMovementReason $warehouseMovementReason): static
    {
        if ($this->warehouseMovementReasons->removeElement($warehouseMovementReason)) {
            // set the owning side to null (unless already changed)
            if ($warehouseMovementReason->getReasonType() === $this) {
                $warehouseMovementReason->setReasonType(null);
            }
        }

        return $this;
    }
}
