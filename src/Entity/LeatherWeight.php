<?php

namespace App\Entity;

use App\Repository\LeatherWeightRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeatherWeightRepository::class)]
class LeatherWeight
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $weight = null;

    #[ORM\Column]
    private ?float $kg_weight = null;

    #[ORM\Column(nullable: true)]
    private ?float $sqft_leather_expected = null;

    #[ORM\Column(nullable: true)]
    private ?float $kg_leather_expected = null;

    #[ORM\Column(nullable: true)]
    private ?float $cost_stripped_crust_various = null;

    #[ORM\Column(nullable: true)]
    private ?float $cost_stripped_crust_manual = null;

    /**
     * @var Collection<int, Leather>
     */
    #[ORM\OneToMany(mappedBy: 'weight', targetEntity: Leather::class)]
    private Collection $leather;

    public function __construct()
    {
        $this->leather = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(string $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getKgWeight(): ?float
    {
        return $this->kg_weight;
    }

    public function setKgWeight(float $kg_weight): static
    {
        $this->kg_weight = $kg_weight;

        return $this;
    }

    public function getSqftLeatherExpected(): ?float
    {
        return $this->sqft_leather_expected;
    }

    public function setSqftLeatherExpected(?float $sqft_leather_expected): static
    {
        $this->sqft_leather_expected = $sqft_leather_expected;

        return $this;
    }

    public function getKgLeatherExpected(): ?float
    {
        return $this->kg_leather_expected;
    }

    public function setKgLeatherExpected(?float $kg_leather_expected): static
    {
        $this->kg_leather_expected = $kg_leather_expected;

        return $this;
    }

    public function getCostStrippedCrustVarious(): ?float
    {
        return $this->cost_stripped_crust_various;
    }

    public function setCostStrippedCrustVarious(?float $cost_stripped_crust_various): static
    {
        $this->cost_stripped_crust_various = $cost_stripped_crust_various;

        return $this;
    }

    public function getCostStrippedCrustManual(): ?float
    {
        return $this->cost_stripped_crust_manual;
    }

    public function setCostStrippedCrustManual(?float $cost_stripped_crust_manual): static
    {
        $this->cost_stripped_crust_manual = $cost_stripped_crust_manual;

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
            $leather->setWeight($this);
        }

        return $this;
    }

    public function removeLeather(Leather $leather): static
    {
        if ($this->leather->removeElement($leather)) {
            // set the owning side to null (unless already changed)
            if ($leather->getWeight() === $this) {
                $leather->setWeight(null);
            }
        }

        return $this;
    }
}
