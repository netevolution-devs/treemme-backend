<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $suspended = null;

    #[ORM\Column(length: 255)]
    private ?string $client_code = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?ContactAddress $address = null;

    #[ORM\Column(nullable: true)]
    private ?float $tolerance_quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $client_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $client_shipment_note = null;

    #[ORM\Column]
    private ?int $tolerance_start_days = null;

    #[ORM\Column(nullable: true)]
    private ?bool $specific_order_reference = null;

    #[ORM\Column(nullable: true)]
    private ?bool $checked = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $check_date = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?User $check_user = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?Payment $payment = null;

    /**
     * @var Collection<int, ClientOrder>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: ClientOrder::class, orphanRemoval: true)]
    private Collection $clientOrders;

    public function __construct()
    {
        $this->clientOrders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isSuspended(): ?bool
    {
        return $this->suspended;
    }

    public function setSuspended(bool $suspended): static
    {
        $this->suspended = $suspended;

        return $this;
    }

    public function getClientCode(): ?string
    {
        return $this->client_code;
    }

    public function setClientCode(string $client_code): static
    {
        $this->client_code = $client_code;

        return $this;
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

    public function getAddress(): ?ContactAddress
    {
        return $this->address;
    }

    public function setAddress(?ContactAddress $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getToleranceQuantity(): ?float
    {
        return $this->tolerance_quantity;
    }

    public function setToleranceQuantity(?float $tolerance_quantity): static
    {
        $this->tolerance_quantity = $tolerance_quantity;

        return $this;
    }

    public function getClientNote(): ?string
    {
        return $this->client_note;
    }

    public function setClientNote(?string $client_note): static
    {
        $this->client_note = $client_note;

        return $this;
    }

    public function getClientShipmentNote(): ?string
    {
        return $this->client_shipment_note;
    }

    public function setClientShipmentNote(?string $client_shipment_note): static
    {
        $this->client_shipment_note = $client_shipment_note;

        return $this;
    }

    public function getToleranceStartDays(): ?int
    {
        return $this->tolerance_start_days;
    }

    public function setToleranceStartDays(int $tolerance_start_days): static
    {
        $this->tolerance_start_days = $tolerance_start_days;

        return $this;
    }

    public function isSpecificOrderReference(): ?bool
    {
        return $this->specific_order_reference;
    }

    public function setSpecificOrderReference(?bool $specific_order_reference): static
    {
        $this->specific_order_reference = $specific_order_reference;

        return $this;
    }

    public function isChecked(): ?bool
    {
        return $this->checked;
    }

    public function setChecked(?bool $checked): static
    {
        $this->checked = $checked;

        return $this;
    }

    public function getCheckDate(): ?\DateTime
    {
        return $this->check_date;
    }

    public function setCheckDate(?\DateTime $check_date): static
    {
        $this->check_date = $check_date;

        return $this;
    }

    public function getCheckUser(): ?User
    {
        return $this->check_user;
    }

    public function setCheckUser(?User $check_user): static
    {
        $this->check_user = $check_user;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

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
            $clientOrder->setClient($this);
        }

        return $this;
    }

    public function removeClientOrder(ClientOrder $clientOrder): static
    {
        if ($this->clientOrders->removeElement($clientOrder)) {
            // set the owning side to null (unless already changed)
            if ($clientOrder->getClient() === $this) {
                $clientOrder->setClient(null);
            }
        }

        return $this;
    }
}
