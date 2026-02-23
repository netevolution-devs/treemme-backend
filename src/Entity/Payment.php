<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['payment_list', 'payment_detail', 'client_detail', 'client_order_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['payment_list', 'payment_detail', 'client_detail', 'client_order_detail'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(mappedBy: 'payment', targetEntity: Client::class)]
    private Collection $clients;

    /**
     * @var Collection<int, ClientOrder>
     */
    #[ORM\OneToMany(mappedBy: 'payment', targetEntity: ClientOrder::class)]
    private Collection $clientOrders;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
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
            $client->setPayment($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getPayment() === $this) {
                $client->setPayment(null);
            }
        }

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
            $clientOrder->setPayment($this);
        }

        return $this;
    }

    public function removeClientOrder(ClientOrder $clientOrder): static
    {
        if ($this->clientOrders->removeElement($clientOrder)) {
            // set the owning side to null (unless already changed)
            if ($clientOrder->getPayment() === $this) {
                $clientOrder->setPayment(null);
            }
        }

        return $this;
    }
}
