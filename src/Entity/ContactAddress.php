<?php

namespace App\Entity;

use App\Repository\ContactAddressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContactAddressRepository::class)]
class ContactAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactAddresses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contact_address_list', 'contact_address_detail'])]
    private ?Contact $contact = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail'])]
    private ?string $address_note = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail'])]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail'])]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail'])]
    private ?string $province = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail'])]
    private ?string $postal_code = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail'])]
    private ?int $weight = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(mappedBy: 'address', targetEntity: Client::class)]
    private Collection $clients;

    #[ORM\ManyToOne(inversedBy: 'contactAddress')]
    private ?Nation $nation = null;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
    }

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

    public function getAddressNote(): ?string
    {
        return $this->address_note;
    }

    public function setAddressNote(?string $address_note): static
    {
        $this->address_note = $address_note;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): static
    {
        $this->province = $province;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setAddress($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getAddress() === $this) {
                $client->setAddress(null);
            }
        }

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
}
