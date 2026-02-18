<?php

namespace App\Entity;

use App\Repository\NationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NationRepository::class)]
class Nation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['nation_list', 'nation_detail', 'leather_provenance_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['nation_list', 'nation_detail', 'leather_provenance_detail'])]
    private ?string $name = null;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[ORM\OneToMany(mappedBy: 'nation', targetEntity: ContactAddress::class)]
    private Collection $contactAddress;

    /**
     * @var Collection<int, LeatherProvenance>
     */
    #[ORM\OneToMany(mappedBy: 'nation', targetEntity: LeatherProvenance::class)]
    private Collection $leatherProvenances;

    public function __construct()
    {
        $this->contactAddress = new ArrayCollection();
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
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddress(): Collection
    {
        return $this->contactAddress;
    }

    public function addContactAddress(ContactAddress $contactAddress): static
    {
        if (!$this->contactAddress->contains($contactAddress)) {
            $this->contactAddress->add($contactAddress);
            $contactAddress->setNation($this);
        }

        return $this;
    }

    public function removeContactAddress(ContactAddress $contactAddress): static
    {
        if ($this->contactAddress->removeElement($contactAddress)) {
            // set the owning side to null (unless already changed)
            if ($contactAddress->getNation() === $this) {
                $contactAddress->setNation(null);
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
            $leatherProvenance->setNation($this);
        }

        return $this;
    }

    public function removeLeatherProvenance(LeatherProvenance $leatherProvenance): static
    {
        if ($this->leatherProvenances->removeElement($leatherProvenance)) {
            // set the owning side to null (unless already changed)
            if ($leatherProvenance->getNation() === $this) {
                $leatherProvenance->setNation(null);
            }
        }

        return $this;
    }
}
