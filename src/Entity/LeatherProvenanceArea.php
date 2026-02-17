<?php

namespace App\Entity;

use App\Repository\LeatherProvenanceAreaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeatherProvenanceAreaRepository::class)]
class LeatherProvenanceArea
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, LeatherProvenance>
     */
    #[ORM\OneToMany(mappedBy: 'area', targetEntity: LeatherProvenance::class)]
    private Collection $leatherProvenances;

    public function __construct()
    {
        $this->leatherProvenances = new ArrayCollection();
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

    /**
     * @return Collection<int, LeatherProvenance>
     */
    public function getLeatherProvenances(): Collection
    {
        return $this->leatherProvenances;
    }

    public function addLeatherProvenance(LeatherProvenance $leatherProvenance): static
    {
        if (!$this->leatherProvenances->contains($leatherProvenance)) {
            $this->leatherProvenances->add($leatherProvenance);
            $leatherProvenance->setArea($this);
        }

        return $this;
    }

    public function removeLeatherProvenance(LeatherProvenance $leatherProvenance): static
    {
        if ($this->leatherProvenances->removeElement($leatherProvenance)) {
            // set the owning side to null (unless already changed)
            if ($leatherProvenance->getArea() === $this) {
                $leatherProvenance->setArea(null);
            }
        }

        return $this;
    }
}
