<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact_list','contact_detail','contact_type_detail', 'leather_list', 'leather_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact_list','contact_detail','contact_type_detail', 'leather_list', 'leather_detail'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact_list','contact_detail'])]
    private ?ContactType $contact_type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['contact_detail','contact_type_detail'])]
    private ?string $contact_note = null;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactAddress::class, orphanRemoval: true)]
    #[Groups(['contact_list','contact_detail'])]
    private Collection $contactAddresses;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, Leather>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Leather::class)]
    private Collection $leather;

    /**
     * @var Collection<int, ContactDetail>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactDetail::class, orphanRemoval: true)]
    #[Groups(['contact_list','contact_detail'])]
    private Collection $contactDetails;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact_list','contact_detail'])]
    private ?ContactTitle $contact_title = null;

    #[ORM\Column]
    #[Groups(['contact_list','contact_detail'])]
    private ?bool $client = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['contact_detail'])]
    private ?float $tolerance_quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['contact_detail'])]
    private ?string $client_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['contact_detail'])]
    private ?string $client_shipment_note = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['contact_detail'])]
    private ?int $tolerance_start_days = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['contact_detail'])]
    private ?bool $specific_order_reference = null;

    #[ORM\Column(nullable: true)]
    private ?bool $checked = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $check_date = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?User $check_user = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?Payment $payment = null;

    #[ORM\Column]
    #[Groups(['contact_list','contact_detail'])]
    private ?bool $supplier = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: Product::class)]
    #[Groups(['supplier_detail'])]
    private Collection $products;

    /**
     * @var Collection<int, Leather>
     */
    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: Leather::class)]
    private Collection $SupplierLeather;

    /**
     * @var Collection<int, ContactAgent>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactAgent::class, orphanRemoval: true)]
    #[Groups(['contact_list','contact_detail'])]
    private Collection $contactAgents;

    #[ORM\OneToMany(mappedBy: 'agent', targetEntity: ContactAgent::class, orphanRemoval: true)]
    private Collection $agentContacts;

    #[ORM\Column]
    #[Groups(['contact_list','contact_detail'])]
    private ?bool $agent = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['contact_list','contact_detail'])]
    private ?float $agent_percentage = null;

    /**
     * @var Collection<int, ClientOrder>
     */
    #[ORM\OneToMany(mappedBy: 'agent', targetEntity: ClientOrder::class)]
    private Collection $clientOrders;

    public function __construct()
    {
        $this->contactAddresses = new ArrayCollection();
        $this->leather = new ArrayCollection();
        $this->contactDetails = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->SupplierLeather = new ArrayCollection();
        $this->contactAgents = new ArrayCollection();
        $this->agentContacts = new ArrayCollection();
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

    public function getContactType(): ?ContactType
    {
        return $this->contact_type;
    }

    public function setContactType(?ContactType $contact_type): static
    {
        $this->contact_type = $contact_type;

        return $this;
    }

    public function getContactNote(): ?string
    {
        return $this->contact_note;
    }

    public function setContactNote(?string $contact_note): static
    {
        $this->contact_note = $contact_note;

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
            $contactAddress->setContact($this);
        }

        return $this;
    }

    public function removeContactAddress(ContactAddress $contactAddress): static
    {
        if ($this->contactAddresses->removeElement($contactAddress)) {
            // set the owning side to null (unless already changed)
            if ($contactAddress->getContact() === $this) {
                $contactAddress->setContact(null);
            }
        }

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
     * @return Collection<int, Leather>
     */
    public function getLeather(): Collection
    {
        return $this->leather;
    }

    public function addLeather(Leather $leather): static
    {
        if (!$this->leather->contains($leather)) {
            $this->leather->add($leather);
            $leather->setContact($this);
        }

        return $this;
    }

    public function removeLeather(Leather $leather): static
    {
        if ($this->leather->removeElement($leather)) {
            // set the owning side to null (unless already changed)
            if ($leather->getContact() === $this) {
                $leather->setContact(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContactDetail>
     */
    public function getContactDetails(): Collection
    {
        return $this->contactDetails;
    }

    public function addContactDetail(ContactDetail $contactDetail): static
    {
        if (!$this->contactDetails->contains($contactDetail)) {
            $this->contactDetails->add($contactDetail);
            $contactDetail->setContact($this);
        }

        return $this;
    }

    public function removeContactDetail(ContactDetail $contactDetail): static
    {
        if ($this->contactDetails->removeElement($contactDetail)) {
            // set the owning side to null (unless already changed)
            if ($contactDetail->getContact() === $this) {
                $contactDetail->setContact(null);
            }
        }

        return $this;
    }

    public function getContactTitle(): ?ContactTitle
    {
        return $this->contact_title;
    }

    public function setContactTitle(?ContactTitle $contact_title): static
    {
        $this->contact_title = $contact_title;

        return $this;
    }

    public function isClient(): ?bool
    {
        return $this->client;
    }

    public function setClient(bool $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function isSupplier(): ?bool
    {
        return $this->supplier;
    }

    public function setSupplier(bool $supplier): static
    {
        $this->supplier = $supplier;

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

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setSupplier($this);
        }

        return $this;
    }

    public function getSupplierLeather(): Collection
    {
        return $this->SupplierLeather;
    }

    public function addSupplierLeather(Leather $supplierLeather): static
    {
        if (!$this->SupplierLeather->contains($supplierLeather)) {
            $this->SupplierLeather->add($supplierLeather);
            $supplierLeather->setSupplier($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ContactAgent>
     */
    public function getContactAgents(): Collection
    {
        return $this->contactAgents;
    }

    public function addContactAgent(ContactAgent $contactAgent): static
    {
        if (!$this->contactAgents->contains($contactAgent)) {
            $this->contactAgents->add($contactAgent);
            $contactAgent->setContact($this);
        }

        return $this;
    }

    public function removeContactAgent(ContactAgent $contactAgent): static
    {
        if ($this->contactAgents->removeElement($contactAgent)) {
            // set the owning side to null (unless already changed)
            if ($contactAgent->getContact() === $this) {
                $contactAgent->setContact(null);
            }
        }

        return $this;
    }

    public function isAgent(): ?bool
    {
        return $this->agent;
    }

    public function setAgent(bool $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getAgentPercentage(): ?float
    {
        return $this->agent_percentage;
    }

    public function setAgentPercentage(?float $agent_percentage): static
    {
        $this->agent_percentage = $agent_percentage;

        return $this;
    }
    /**
     * @return Collection<int, ContactAgent>
     */
    public function getAgentContacts(): Collection
    {
        return $this->agentContacts;
    }

    public function addAgentContact(ContactAgent $agentContact): static
    {
        if (!$this->agentContacts->contains($agentContact)) {
            $this->agentContacts->add($agentContact);
            $agentContact->setAgent($this);
        }

        return $this;
    }

    public function removeAgentContact(ContactAgent $agentContact): static
    {
        if ($this->agentContacts->removeElement($agentContact)) {
            // set the owning side to null (unless already changed)
            if ($agentContact->getAgent() === $this) {
                $agentContact->setAgent(null);
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
            $clientOrder->setAgent($this);
        }

        return $this;
    }

    public function removeClientOrder(ClientOrder $clientOrder): static
    {
        if ($this->clientOrders->removeElement($clientOrder)) {
            // set the owning side to null (unless already changed)
            if ($clientOrder->getAgent() === $this) {
                $clientOrder->setAgent(null);
            }
        }

        return $this;
    }
}
