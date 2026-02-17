<?php

namespace App\Entity;

use App\Repository\ClientOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientOrderRepository::class)]
class ClientOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $processed = null;

    #[ORM\Column]
    private ?bool $cancelled = null;

    #[ORM\Column]
    private ?bool $checked = null;

    #[ORM\ManyToOne(inversedBy: 'clientOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $order_number = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $order_date = null;

    #[ORM\Column(nullable: true)]
    private ?float $percentage_agent = null;

    #[ORM\Column(length: 255)]
    private ?string $client_order_number = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $client_order_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agent_order_number = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $agent_order_date = null;

    #[ORM\ManyToOne(inversedBy: 'clientOrders')]
    private ?Payment $payment = null;

    #[ORM\Column(nullable: true)]
    private ?float $percentage_tolerance_quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $order_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $order_note_ISO = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $order_note_production = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $order_note_administration = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $check_date = null;

    #[ORM\ManyToOne(inversedBy: 'clientOrders')]
    private ?User $check_user = null;

    #[ORM\Column]
    private ?bool $printed = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $print_date = null;

    /**
     * @var Collection<int, ClientOrderRow>
     */
    #[ORM\OneToMany(mappedBy: 'client_order', targetEntity: ClientOrderRow::class, orphanRemoval: true)]
    private Collection $clientOrderRows;

    public function __construct()
    {
        $this->clientOrderRows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isProcessed(): ?bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): static
    {
        $this->processed = $processed;

        return $this;
    }

    public function isCancelled(): ?bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): static
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    public function isChecked(): ?bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): static
    {
        $this->checked = $checked;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->order_number;
    }

    public function setOrderNumber(?string $order_number): static
    {
        $this->order_number = $order_number;

        return $this;
    }

    public function getOrderDate(): ?\DateTime
    {
        return $this->order_date;
    }

    public function setOrderDate(?\DateTime $order_date): static
    {
        $this->order_date = $order_date;

        return $this;
    }

    public function getPercentageAgent(): ?float
    {
        return $this->percentage_agent;
    }

    public function setPercentageAgent(?float $percentage_agent): static
    {
        $this->percentage_agent = $percentage_agent;

        return $this;
    }

    public function getClientOrderNumber(): ?string
    {
        return $this->client_order_number;
    }

    public function setClientOrderNumber(string $client_order_number): static
    {
        $this->client_order_number = $client_order_number;

        return $this;
    }

    public function getClientOrderDate(): ?\DateTime
    {
        return $this->client_order_date;
    }

    public function setClientOrderDate(?\DateTime $client_order_date): static
    {
        $this->client_order_date = $client_order_date;

        return $this;
    }

    public function getAgentOrderNumber(): ?string
    {
        return $this->agent_order_number;
    }

    public function setAgentOrderNumber(?string $agent_order_number): static
    {
        $this->agent_order_number = $agent_order_number;

        return $this;
    }

    public function getAgentOrderDate(): ?\DateTime
    {
        return $this->agent_order_date;
    }

    public function setAgentOrderDate(?\DateTime $agent_order_date): static
    {
        $this->agent_order_date = $agent_order_date;

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

    public function getPercentageToleranceQuantity(): ?float
    {
        return $this->percentage_tolerance_quantity;
    }

    public function setPercentageToleranceQuantity(?float $percentage_tolerance_quantity): static
    {
        $this->percentage_tolerance_quantity = $percentage_tolerance_quantity;

        return $this;
    }

    public function getOrderNote(): ?string
    {
        return $this->order_note;
    }

    public function setOrderNote(?string $order_note): static
    {
        $this->order_note = $order_note;

        return $this;
    }

    public function getOrderNoteISO(): ?string
    {
        return $this->order_note_ISO;
    }

    public function setOrderNoteISO(?string $order_note_ISO): static
    {
        $this->order_note_ISO = $order_note_ISO;

        return $this;
    }

    public function getOrderNoteProduction(): ?string
    {
        return $this->order_note_production;
    }

    public function setOrderNoteProduction(?string $order_note_production): static
    {
        $this->order_note_production = $order_note_production;

        return $this;
    }

    public function getOrderNoteAdministration(): ?string
    {
        return $this->order_note_administration;
    }

    public function setOrderNoteAdministration(?string $order_note_administration): static
    {
        $this->order_note_administration = $order_note_administration;

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

    public function isPrinted(): ?bool
    {
        return $this->printed;
    }

    public function setPrinted(bool $printed): static
    {
        $this->printed = $printed;

        return $this;
    }

    public function getPrintDate(): ?\DateTime
    {
        return $this->print_date;
    }

    public function setPrintDate(?\DateTime $print_date): static
    {
        $this->print_date = $print_date;

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
            $clientOrderRow->setClientOrder($this);
        }

        return $this;
    }

    public function removeClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if ($this->clientOrderRows->removeElement($clientOrderRow)) {
            // set the owning side to null (unless already changed)
            if ($clientOrderRow->getClientOrder() === $this) {
                $clientOrderRow->setClientOrder(null);
            }
        }

        return $this;
    }
}
