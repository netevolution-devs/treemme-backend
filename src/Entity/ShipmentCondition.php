<?php

namespace App\Entity;

use App\Repository\ShipmentConditionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ShipmentConditionRepository::class)]
class ShipmentCondition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['client_order_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client_order_detail'])]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $borne_by_customer = null;

    /**
     * @var Collection<int, ClientOrder>
     */
    #[ORM\OneToMany(mappedBy: 'shipment_condition', targetEntity: ClientOrder::class)]
    private Collection $clientOrders;

    public function __construct()
    {
        $this->clientOrders = new ArrayCollection();
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

    public function isBorneByCustomer(): ?bool
    {
        return $this->borne_by_customer;
    }

    public function setBorneByCustomer(bool $borne_by_customer): static
    {
        $this->borne_by_customer = $borne_by_customer;

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
            $clientOrder->setShipmentCondition($this);
        }

        return $this;
    }

    public function removeClientOrder(ClientOrder $clientOrder): static
    {
        if ($this->clientOrders->removeElement($clientOrder)) {
            // set the owning side to null (unless already changed)
            if ($clientOrder->getShipmentCondition() === $this) {
                $clientOrder->setShipmentCondition(null);
            }
        }

        return $this;
    }
}
