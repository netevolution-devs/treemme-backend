<?php

namespace App\Entity;

use App\Repository\LeatherFlayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeatherFlayRepository::class)]
class LeatherFlay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['leather_flay_list', 'leather_flay_detail', 'leather_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['leather_flay_list', 'leather_flay_detail', 'leather_detail'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['leather_flay_list', 'leather_flay_detail', 'leather_detail'])]
    private ?string $code = null;

    /**
     * @var Collection<int, Leather>
     */
    #[ORM\OneToMany(mappedBy: 'flay', targetEntity: Leather::class)]
    private Collection $leather;

    /**
     * @var Collection<int, LeatherProvenance>
     */
    #[ORM\OneToMany(mappedBy: 'flay', targetEntity: LeatherProvenance::class)]
    private Collection $leatherProvenances;

    public function __construct()
    {
        $this->leather = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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
            $leather->setFlay($this);
        }

        return $this;
    }

    public function removeLeather(Leather $leather): static
    {
        if ($this->leather->removeElement($leather)) {
            // set the owning side to null (unless already changed)
            if ($leather->getFlay() === $this) {
                $leather->setFlay(null);
            }
        }

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
            $leatherProvenance->setFlay($this);
        }

        return $this;
    }

    public function removeLeatherProvenance(LeatherProvenance $leatherProvenance): static
    {
        if ($this->leatherProvenances->removeElement($leatherProvenance)) {
            // set the owning side to null (unless already changed)
            if ($leatherProvenance->getFlay() === $this) {
                $leatherProvenance->setFlay(null);
            }
        }

        return $this;
    }
}
