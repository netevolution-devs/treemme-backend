<?php

namespace App\Entity;

use App\Repository\NationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NationRepository::class)]
class Nation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[ORM\OneToMany(mappedBy: 'nation', targetEntity: ContactAddress::class)]
    private Collection $contactAddress;

    public function __construct()
    {
        $this->contactAddress = new ArrayCollection();
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
}
