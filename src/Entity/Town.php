<?php

namespace App\Entity;

use App\Repository\TownRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TownRepository::class)]
class Town
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['town_list', 'town_detail', 'province_detail', 'contact_address_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['town_list', 'town_detail', 'province_detail', 'contact_address_detail'])]
    private ?string $cap = null;

    #[ORM\Column(length: 255)]
    #[Groups(['town_list', 'town_detail', 'province_detail', 'contact_address_detail'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'town')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['town_list', 'town_detail'])]
    private ?Province $province = null;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[ORM\OneToMany(mappedBy: 'town', targetEntity: ContactAddress::class)]
    private Collection $contactAddresses;

    public function __construct()
    {
        $this->contactAddresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCap(): ?string
    {
        return $this->cap;
    }

    public function setCap(string $cap): static
    {
        $this->cap = $cap;

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

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): static
    {
        $this->province = $province;

        return $this;
    }

    /**
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddresses(): Collection
    {
        return $this->contactAddresses;
    }

    public function addContactAddress(ContactAddress $contactAddress): static
    {
        if (!$this->contactAddresses->contains($contactAddress)) {
            $this->contactAddresses->add($contactAddress);
            $contactAddress->setTown($this);
        }

        return $this;
    }

    public function removeContactAddress(ContactAddress $contactAddress): static
    {
        if ($this->contactAddresses->removeElement($contactAddress)) {
            // set the owning side to null (unless already changed)
            if ($contactAddress->getTown() === $this) {
                $contactAddress->setTown(null);
            }
        }

        return $this;
    }
}
