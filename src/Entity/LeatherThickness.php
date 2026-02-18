<?php

namespace App\Entity;

use App\Repository\LeatherThicknessRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeatherThicknessRepository::class)]
class LeatherThickness
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['leather_thickness_list', 'leather_thickness_detail', 'leather_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['leather_thickness_list', 'leather_thickness_detail', 'leather_detail'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_thickness_list', 'leather_thickness_detail', 'leather_detail'])]
    private ?float $thickness_mm = null;

    /**
     * @var Collection<int, LeatherType>
     */
    #[ORM\OneToMany(mappedBy: 'thickness', targetEntity: LeatherType::class)]
    private Collection $leatherTypes;

    /**
     * @var Collection<int, Leather>
     */
    #[ORM\OneToMany(mappedBy: 'thickness', targetEntity: Leather::class)]
    private Collection $leather;

    public function __construct()
    {
        $this->leatherTypes = new ArrayCollection();
        $this->leather = new ArrayCollection();
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

    public function getThicknessMm(): ?float
    {
        return $this->thickness_mm;
    }

    public function setThicknessMm(?float $thickness_mm): static
    {
        $this->thickness_mm = $thickness_mm;

        return $this;
    }

    /**
     * @return Collection<int, LeatherType>
     */
    public function getLeatherTypes(): Collection
    {
        return $this->leatherTypes;
    }

    public function addLeatherType(LeatherType $leatherType): static
    {
        if (!$this->leatherTypes->contains($leatherType)) {
            $this->leatherTypes->add($leatherType);
            $leatherType->setThickness($this);
        }

        return $this;
    }

    public function removeLeatherType(LeatherType $leatherType): static
    {
        if ($this->leatherTypes->removeElement($leatherType)) {
            // set the owning side to null (unless already changed)
            if ($leatherType->getThickness() === $this) {
                $leatherType->setThickness(null);
            }
        }

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
            $leather->setThickness($this);
        }

        return $this;
    }

    public function removeLeather(Leather $leather): static
    {
        if ($this->leather->removeElement($leather)) {
            // set the owning side to null (unless already changed)
            if ($leather->getThickness() === $this) {
                $leather->setThickness(null);
            }
        }

        return $this;
    }
}
