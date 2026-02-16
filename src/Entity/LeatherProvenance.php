<?php

namespace App\Entity;

use App\Repository\LeatherProvenanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeatherProvenanceRepository::class)]
class LeatherProvenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'leatherProvenances')]
    private ?LeatherProvenanceArea $area = null;

    #[ORM\ManyToOne(inversedBy: 'leatherProvenances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Nation $nation = null;

    #[ORM\Column(nullable: true)]
    private ?int $trip_day = null;

    #[ORM\ManyToOne(inversedBy: 'leatherProvenances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LeatherFlay $flay = null;

    #[ORM\Column]
    private ?float $psp_yield_coefficent = null;

    #[ORM\Column]
    private ?float $grain_yield_coefficent = null;

    #[ORM\Column]
    private ?float $crust_yield_coefficent = null;

    #[ORM\Column]
    private ?bool $sea_shipment = null;

    /**
     * @var Collection<int, Leather>
     */
    #[ORM\OneToMany(mappedBy: 'provenance', targetEntity: Leather::class)]
    private Collection $leather;

    public function __construct()
    {
        $this->leather = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getArea(): ?LeatherProvenanceArea
    {
        return $this->area;
    }

    public function setArea(?LeatherProvenanceArea $area): static
    {
        $this->area = $area;

        return $this;
    }

    public function getNation(): ?Nation
    {
        return $this->nation;
    }

    public function setNation(?Nation $nation): static
    {
        $this->nation = $nation;

        return $this;
    }

    public function getTripDay(): ?int
    {
        return $this->trip_day;
    }

    public function setTripDay(?int $trip_day): static
    {
        $this->trip_day = $trip_day;

        return $this;
    }

    public function getFlay(): ?LeatherFlay
    {
        return $this->flay;
    }

    public function setFlay(?LeatherFlay $flay): static
    {
        $this->flay = $flay;

        return $this;
    }

    public function getPspYieldCoefficent(): ?float
    {
        return $this->psp_yield_coefficent;
    }

    public function setPspYieldCoefficent(float $psp_yield_coefficent): static
    {
        $this->psp_yield_coefficent = $psp_yield_coefficent;

        return $this;
    }

    public function getGrainYieldCoefficent(): ?float
    {
        return $this->grain_yield_coefficent;
    }

    public function setGrainYieldCoefficent(float $grain_yield_coefficent): static
    {
        $this->grain_yield_coefficent = $grain_yield_coefficent;

        return $this;
    }

    public function getCrustYieldCoefficent(): ?float
    {
        return $this->crust_yield_coefficent;
    }

    public function setCrustYieldCoefficent(float $crust_yield_coefficent): static
    {
        $this->crust_yield_coefficent = $crust_yield_coefficent;

        return $this;
    }

    public function isSeaShipment(): ?bool
    {
        return $this->sea_shipment;
    }

    public function setSeaShipment(bool $sea_shipment): static
    {
        $this->sea_shipment = $sea_shipment;

        return $this;
    }

    /**
     * @return Collection<int, Leather>
     */
    public function getLeather(): Collection
    {
        return $this->leather;
    }

    public function addLeather(Leather $leather): static
    {
        if (!$this->leather->contains($leather)) {
            $this->leather->add($leather);
            $leather->setProvenance($this);
        }

        return $this;
    }

    public function removeLeather(Leather $leather): static
    {
        if ($this->leather->removeElement($leather)) {
            // set the owning side to null (unless already changed)
            if ($leather->getProvenance() === $this) {
                $leather->setProvenance(null);
            }
        }

        return $this;
    }
}
