<?php

namespace App\Entity;

use App\Repository\ShippingCarrierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ShippingCarrierRepository::class)]
class ShippingCarrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['shipping_carrier_list', 'shipping_carrier_detail', 'client_order_detail', 'contact_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['shipping_carrier_list', 'shipping_carrier_detail', 'client_order_detail', 'contact_detail'])]
    private ?string $name = null;

    /**
     * @var Collection<int, ClientOrder>
     */
    #[ORM\OneToMany(mappedBy: 'shipping_carrier', targetEntity: ClientOrder::class)]
    private Collection $clientOrders;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(mappedBy: 'shipping_carrier', targetEntity: Contact::class)]
    private Collection $contacts;

    public function __construct()
    {
        $this->clientOrders = new ArrayCollection();
        $this->contacts = new ArrayCollection();
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
            $clientOrder->setShippingCarrier($this);
        }

        return $this;
    }

    public function removeClientOrder(ClientOrder $clientOrder): static
    {
        if ($this->clientOrders->removeElement($clientOrder)) {
            // set the owning side to null (unless already changed)
            if ($clientOrder->getShippingCarrier() === $this) {
                $clientOrder->setShippingCarrier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setShippingCarrier($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getShippingCarrier() === $this) {
                $contact->setShippingCarrier(null);
            }
        }

        return $this;
    }
}
