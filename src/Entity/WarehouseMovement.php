<?php

namespace App\Entity;

use App\Repository\WarehouseMovementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WarehouseMovementRepository::class)]
class WarehouseMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'warehouseMovements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'warehouseMovements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WarehouseMovementReason $reason = null;

    #[ORM\Column(nullable: true)]
    private ?int $piece = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column]
    private ?float $quantity = null;

    #[ORM\Column(nullable: true)]
    private ?float $total_value = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ddt_number = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $ddt_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $movement_note = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'sonWarehouseMovements')]
    private ?self $father_movement = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'father_movement', targetEntity: self::class)]
    private Collection $sonWarehouseMovements;

    public function __construct()
    {
        $this->sonWarehouseMovements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    public function setBatch(?Batch $batch): static
    {
        $this->batch = $batch;

        return $this;
    }

    public function getReason(): ?WarehouseMovementReason
    {
        return $this->reason;
    }

    public function setReason(?WarehouseMovementReason $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getPiece(): ?int
    {
        return $this->piece;
    }

    public function setPiece(?int $piece): static
    {
        $this->piece = $piece;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTotalValue(): ?float
    {
        return $this->total_value;
    }

    public function setTotalValue(?float $total_value): static
    {
        $this->total_value = $total_value;

        return $this;
    }

    public function getDdtNumber(): ?string
    {
        return $this->ddt_number;
    }

    public function setDdtNumber(?string $ddt_number): static
    {
        $this->ddt_number = $ddt_number;

        return $this;
    }

    public function getDdtDate(): ?\DateTime
    {
        return $this->ddt_date;
    }

    public function setDdtDate(?\DateTime $ddt_date): static
    {
        $this->ddt_date = $ddt_date;

        return $this;
    }

    public function getMovementNote(): ?string
    {
        return $this->movement_note;
    }

    public function setMovementNote(?string $movement_note): static
    {
        $this->movement_note = $movement_note;

        return $this;
    }

    public function getFatherMovement(): ?self
    {
        return $this->father_movement;
    }

    public function setFatherMovement(?self $father_movement): static
    {
        $this->father_movement = $father_movement;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSonWarehouseMovements(): Collection
    {
        return $this->sonWarehouseMovements;
    }

    public function addSonWarehouseMovement(self $sonWarehouseMovement): static
    {
        if (!$this->sonWarehouseMovements->contains($sonWarehouseMovement)) {
            $this->sonWarehouseMovements->add($sonWarehouseMovement);
            $sonWarehouseMovement->setFatherMovement($this);
        }

        return $this;
    }

    public function removeSonWarehouseMovement(self $sonWarehouseMovement): static
    {
        if ($this->sonWarehouseMovements->removeElement($sonWarehouseMovement)) {
            // set the owning side to null (unless already changed)
            if ($sonWarehouseMovement->getFatherMovement() === $this) {
                $sonWarehouseMovement->setFatherMovement(null);
            }
        }

        return $this;
    }
}
