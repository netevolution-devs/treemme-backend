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
    #[Groups(['shipmentCondition_detail',  'shipmentCondition_list', 'client_order_detail', 'contact_list', 'contact_detail', 'batch_data_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['shipmentCondition_detail',  'shipmentCondition_list', 'client_order_detail', 'contact_list', 'contact_detail', 'batch_data_detail', 'client_summary_print'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['shipmentCondition_detail'])]
    private ?bool $borne_by_customer = null;

    /**
     * @var Collection<int, ClientOrder>
     */
    #[ORM\OneToMany(mappedBy: 'shipment_condition', targetEntity: ClientOrder::class)]
    private Collection $clientOrders;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(mappedBy: 'shipment_condition', targetEntity: Contact::class)]
    private Collection $contacts;

    /**
     * @var Collection<int, BatchData>
     */
    #[ORM\OneToMany(mappedBy: 'shipmentCondition', targetEntity: BatchData::class)]
    private Collection $batchData;

    public function __construct()
    {
        $this->clientOrders = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->batchData = new ArrayCollection();
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
            $contact->setShipmentCondition($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getShipmentCondition() === $this) {
                $contact->setShipmentCondition(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BatchData>
     */
    public function getBatchData(): Collection
    {
        return $this->batchData;
    }

    public function addBatchData(BatchData $batchData): static
    {
        if (!$this->batchData->contains($batchData)) {
            $this->batchData->add($batchData);
            $batchData->setShipmentCondition($this);
        }

        return $this;
    }

    public function removeBatchData(BatchData $batchData): static
    {
        if ($this->batchData->removeElement($batchData)) {
            // set the owning side to null (unless already changed)
            if ($batchData->getShipmentCondition() === $this) {
                $batchData->setShipmentCondition(null);
            }
        }

        return $this;
    }
}
