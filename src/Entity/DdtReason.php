<?php

namespace App\Entity;

use App\Repository\DdtReasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DdtReasonRepository::class)]
class DdtReason
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ddt_reason_list', 'ddt_reason_detail', 'ddt_list', 'ddt_detail', 'client_summary_print'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ddt_reason_list', 'ddt_reason_detail', 'ddt_list', 'ddt_detail', 'client_summary_print'])]
    private ?string $name = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['ddt_reason_list', 'ddt_reason_detail'])]
    private bool $is_shipment_reason = false;

    #[ORM\ManyToOne(inversedBy: 'ddtReasons')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ddt_reason_list', 'ddt_reason_detail'])]
    private ?WarehouseMovementReason $warehouse_movement_reason = null;

    /**
     * @var Collection<int, Ddt>
     */
    #[ORM\OneToMany(mappedBy: 'reason', targetEntity: Ddt::class)]
    private Collection $ddts;

    public function __construct()
    {
        $this->ddts = new ArrayCollection();
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

    public function isIsShipmentReason(): bool
    {
        return $this->is_shipment_reason;
    }

    public function setIsShipmentReason(bool $is_shipment_reason): static
    {
        $this->is_shipment_reason = $is_shipment_reason;

        return $this;
    }

    public function getWarehouseMovementReason(): ?WarehouseMovementReason
    {
        return $this->warehouse_movement_reason;
    }

    public function setWarehouseMovementReason(?WarehouseMovementReason $warehouse_movement_reason): static
    {
        $this->warehouse_movement_reason = $warehouse_movement_reason;

        return $this;
    }

    /**
     * @return Collection<int, Ddt>
     */
    public function getDdts(): Collection
    {
        return $this->ddts;
    }

    public function addDdt(Ddt $ddt): static
    {
        if (!$this->ddts->contains($ddt)) {
            $this->ddts->add($ddt);
            $ddt->setReason($this);
        }

        return $this;
    }

    public function removeDdt(Ddt $ddt): static
    {
        if ($this->ddts->removeElement($ddt)) {
            // set the owning side to null (unless already changed)
            if ($ddt->getReason() === $this) {
                $ddt->setReason(null);
            }
        }

        return $this;
    }
}
