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
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail','client_order_detail', 'client_order_row_list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactAddresses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contact_address_list', 'contact_address_detail'])]
    private ?Contact $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail','client_order_detail', 'client_order_row_list'])]
    private ?string $address_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail','client_order_detail', 'client_order_row_list'])]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail','client_order_detail', 'client_order_row_list'])]
    private ?string $address_2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail', 'client_order_row_list'])]
    private ?string $address_3 = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail', 'client_order_row_list'])]
    private ?string $address_4 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail'])]
    private ?int $weight = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;


    #[ORM\ManyToOne(inversedBy: 'contactAddress')]
    #[Groups(['contact_address_detail', 'contact_detail'])]
    private ?Nation $nation = null;



    /**
     * @var Collection<int, ClientOrder>
     */
    #[ORM\OneToMany(mappedBy: 'address', targetEntity: ClientOrder::class)]
    private Collection $clientOrders;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact_address_list', 'contact_address_detail', 'contact_detail','client_order_detail', 'client_order_row_list'])]
    private ?string $zip_code = null;

    /**
     * @var Collection<int, ClientOrderRow>
     */
    #[ORM\OneToMany(mappedBy: 'address', targetEntity: ClientOrderRow::class)]
    private Collection $clientOrderRows;

    public function __construct()
    {
        $this->clientOrders = new ArrayCollection();
        $this->clientOrderRows = new ArrayCollection();
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

    public function getAddressName(): ?string
    {
        return $this->address_name;
    }

    public function setAddressName(?string $address_name): static
    {
        $this->address_name = $address_name;

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

    public function setAddress3(?string $address_3): static
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

    public function getNation(): ?Nation
    {
        return $this->nation;
    }

    public function setNation(?Nation $nation): static
    {
        $this->nation = $nation;

        return $this;
    }

    /**
     * @return Collection<int, ClientOrder>
     */
    public function getClientOrders(): Collection
    {
        return $this->clientOrders;
    }

    public function addClientOrder(ClientOrder $clientOrder): static
    {
        if (!$this->clientOrders->contains($clientOrder)) {
            $this->clientOrders->add($clientOrder);
            $clientOrder->setAddress($this);
        }

        return $this;
    }

    public function removeClientOrder(ClientOrder $clientOrder): static
    {
        if ($this->clientOrders->removeElement($clientOrder)) {
            // set the owning side to null (unless already changed)
            if ($clientOrder->getAddress() === $this) {
                $clientOrder->setAddress(null);
            }
        }

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    public function setZipCode(?string $zip_code): static
    {
        $this->zip_code = $zip_code;

        return $this;
    }

    /**
     * @return Collection<int, ClientOrderRow>
     */
    public function getClientOrderRows(): Collection
    {
        return $this->clientOrderRows;
    }

    public function addClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if (!$this->clientOrderRows->contains($clientOrderRow)) {
            $this->clientOrderRows->add($clientOrderRow);
            $clientOrderRow->setAddress($this);
        }

        return $this;
    }

    public function removeClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if ($this->clientOrderRows->removeElement($clientOrderRow)) {
            // set the owning side to null (unless already changed)
            if ($clientOrderRow->getAddress() === $this) {
                $clientOrderRow->setAddress(null);
            }
        }

        return $this;
    }
}
