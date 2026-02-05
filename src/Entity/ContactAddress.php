<?php

namespace App\Entity;

use App\Repository\ContactAddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactAddressRepository::class)]
class ContactAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactAddresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_2 = null;

    #[ORM\Column(length: 255)]
    private ?string $address_3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_4 = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postal_code = null;

    #[ORM\Column(nullable: true)]
    private ?int $weight = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getAddress1(): ?string
    {
        return $this->address_1;
    }

    public function setAddress1(?string $address_1): static
    {
        $this->address_1 = $address_1;

        return $this;
    }

    public function getAddress2(): ?string
    {
        return $this->address_2;
    }

    public function setAddress2(?string $address_2): static
    {
        $this->address_2 = $address_2;

        return $this;
    }

    public function getAddress3(): ?string
    {
        return $this->address_3;
    }

    public function setAddress3(string $address_3): static
    {
        $this->address_3 = $address_3;

        return $this;
    }

    public function getAddress4(): ?string
    {
        return $this->address_4;
    }

    public function setAddress4(?string $address_4): static
    {
        $this->address_4 = $address_4;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postal_code;
    }

    public function setPostalCode(?string $postal_code): static
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }
}
