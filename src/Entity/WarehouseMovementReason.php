<?php

namespace App\Entity;

use App\Repository\WarehouseMovementReasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WarehouseMovementReasonRepository::class)]
class WarehouseMovementReason
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'warehouseMovementReasons')]
    private ?WarehouseMovementReasonType $reason_type = null;

    /**
     * @var Collection<int, WarehouseMovement>
     */
    #[ORM\OneToMany(mappedBy: 'reason', targetEntity: WarehouseMovement::class)]
    private Collection $warehouseMovements;

    public function __construct()
    {
        $this->warehouseMovements = new ArrayCollection();
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

    public function getReasonType(): ?WarehouseMovementReasonType
    {
        return $this->reason_type;
    }

    public function setReasonType(?WarehouseMovementReasonType $reason_type): static
    {
        $this->reason_type = $reason_type;

        return $this;
    }

    /**
     * @return Collection<int, WarehouseMovement>
     */
    public function getWarehouseMovements(): Collection
    {
        return $this->warehouseMovements;
    }

    public function addWarehouseMovement(WarehouseMovement $warehouseMovement): static
    {
        if (!$this->warehouseMovements->contains($warehouseMovement)) {
            $this->warehouseMovements->add($warehouseMovement);
            $warehouseMovement->setReason($this);
        }

        return $this;
    }

    public function removeWarehouseMovement(WarehouseMovement $warehouseMovement): static
    {
        if ($this->warehouseMovements->removeElement($warehouseMovement)) {
            // set the owning side to null (unless already changed)
            if ($warehouseMovement->getReason() === $this) {
                $warehouseMovement->setReason(null);
            }
        }

        return $this;
    }
}
