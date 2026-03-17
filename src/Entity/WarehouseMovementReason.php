<?php

namespace App\Entity;

use App\Repository\WarehouseMovementReasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WarehouseMovementReasonRepository::class)]
class WarehouseMovementReason
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reason_list', 'reason_detail', 'batch_detail', 'ddt_reason_list', 'ddt_reason_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['reason_list', 'reason_detail', 'batch_detail', 'ddt_reason_list', 'ddt_reason_detail'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'warehouseMovementReasons')]
    #[Groups(['reason_list', 'reason_detail'])]
    private ?WarehouseMovementReasonType $reason_type = null;

    /**
     * @var Collection<int, WarehouseMovement>
     */
    #[ORM\OneToMany(mappedBy: 'reason', targetEntity: WarehouseMovement::class)]
    #[Groups(['reason_detail'])]
    private Collection $warehouseMovements;

    /**
     * @var Collection<int, DdtReason>
     */
    #[ORM\OneToMany(mappedBy: 'warehouse_movement_reason', targetEntity: DdtReason::class, orphanRemoval: true)]
    #[Groups(['reason_detail'])]
    private Collection $ddtReasons;

    public function __construct()
    {
        $this->warehouseMovements = new ArrayCollection();
        $this->ddtReasons = new ArrayCollection();
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

    /**
     * @return Collection<int, DdtReason>
     */
    public function getDdtReasons(): Collection
    {
        return $this->ddtReasons;
    }

    public function addDdtReason(DdtReason $ddtReason): static
    {
        if (!$this->ddtReasons->contains($ddtReason)) {
            $this->ddtReasons->add($ddtReason);
            $ddtReason->setWarehouseMovementReason($this);
        }

        return $this;
    }

    public function removeDdtReason(DdtReason $ddtReason): static
    {
        if ($this->ddtReasons->removeElement($ddtReason)) {
            // set the owning side to null (unless already changed)
            if ($ddtReason->getWarehouseMovementReason() === $this) {
                $ddtReason->setWarehouseMovementReason(null);
            }
        }

        return $this;
    }
}
