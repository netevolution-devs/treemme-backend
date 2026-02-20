<?php

namespace App\Entity;

use App\Repository\ProvinceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProvinceRepository::class)]
class Province
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['province_list', 'province_detail', 'town_list', 'town_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    #[Groups(['province_list', 'province_detail', 'town_list', 'town_detail'])]
    private ?string $acronym = null;

    #[ORM\Column(length: 255)]
    #[Groups(['province_list', 'province_detail', 'town_list', 'town_detail'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Town>
     */
    #[ORM\OneToMany(mappedBy: 'province', targetEntity: Town::class, orphanRemoval: true)]
    #[Groups(['province_detail'])]
    private Collection $town;

    public function __construct()
    {
        $this->town = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAcronym(): ?string
    {
        return $this->acronym;
    }

    public function setAcronym(string $acronym): static
    {
        $this->acronym = $acronym;

        return $this;
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
     * @return Collection<int, Town>
     */
    public function getTown(): Collection
    {
        return $this->town;
    }

    public function addTown(Town $town): static
    {
        if (!$this->town->contains($town)) {
            $this->town->add($town);
            $town->setProvince($this);
        }

        return $this;
    }

    public function removeTown(Town $town): static
    {
        if ($this->town->removeElement($town)) {
            // set the owning side to null (unless already changed)
            if ($town->getProvince() === $this) {
                $town->setProvince(null);
            }
        }

        return $this;
    }
}
