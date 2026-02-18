<?php

namespace App\Entity;

use App\Repository\ClientOrderRowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClientOrderRowRepository::class)]
class ClientOrderRow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'clientOrderRows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['client_order_row_list', 'client_order_row_detail'])]
    private ?ClientOrder $client_order = null;

    #[ORM\Column]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?bool $processed = null;

    #[ORM\Column]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?bool $cancelled = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?int $weight = null;

    #[ORM\ManyToOne(inversedBy: 'clientOrderRows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'clientOrderRows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?MeasurementUnit $measurement_unit = null;

    #[ORM\Column]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?int $quantity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $total_price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $currency_price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $currency_exchange = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $total_currency_price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $agent_percentage_row = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $tolerance_quantity_percentage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $shipment_schedule = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?float $production_schedule = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?\DateTime $delivey_date_request = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?\DateTime $delivery_date_confirmed = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?string $iso_row_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?string $production_row_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['client_order_row_list', 'client_order_row_detail', 'client_order_detail'])]
    private ?string $administration_row_note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientOrder(): ?ClientOrder
    {
        return $this->client_order;
    }

    public function setClientOrder(?ClientOrder $client_order): static
    {
        $this->client_order = $client_order;

        return $this;
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

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getMeasurementUnit(): ?MeasurementUnit
    {
        return $this->measurement_unit;
    }

    public function setMeasurementUnit(?MeasurementUnit $measurement_unit): static
    {
        $this->measurement_unit = $measurement_unit;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->total_price;
    }

    public function setTotalPrice(?float $total_price): static
    {
        $this->total_price = $total_price;

        return $this;
    }

    public function getCurrencyPrice(): ?float
    {
        return $this->currency_price;
    }

    public function setCurrencyPrice(?float $currency_price): static
    {
        $this->currency_price = $currency_price;

        return $this;
    }

    public function getCurrencyExchange(): ?float
    {
        return $this->currency_exchange;
    }

    public function setCurrencyExchange(?float $currency_exchange): static
    {
        $this->currency_exchange = $currency_exchange;

        return $this;
    }

    public function getTotalCurrencyPrice(): ?float
    {
        return $this->total_currency_price;
    }

    public function setTotalCurrencyPrice(?float $total_currency_price): static
    {
        $this->total_currency_price = $total_currency_price;

        return $this;
    }

    public function getAgentPercentageRow(): ?float
    {
        return $this->agent_percentage_row;
    }

    public function setAgentPercentageRow(?float $agent_percentage_row): static
    {
        $this->agent_percentage_row = $agent_percentage_row;

        return $this;
    }

    public function getToleranceQuantityPercentage(): ?float
    {
        return $this->tolerance_quantity_percentage;
    }

    public function setToleranceQuantityPercentage(?float $tolerance_quantity_percentage): static
    {
        $this->tolerance_quantity_percentage = $tolerance_quantity_percentage;

        return $this;
    }

    public function getShipmentSchedule(): ?float
    {
        return $this->shipment_schedule;
    }

    public function setShipmentSchedule(?float $shipment_schedule): static
    {
        $this->shipment_schedule = $shipment_schedule;

        return $this;
    }

    public function getProductionSchedule(): ?float
    {
        return $this->production_schedule;
    }

    public function setProductionSchedule(?float $production_schedule): static
    {
        $this->production_schedule = $production_schedule;

        return $this;
    }

    public function getDeliveyDateRequest(): ?\DateTime
    {
        return $this->delivey_date_request;
    }

    public function setDeliveyDateRequest(?\DateTime $delivey_date_request): static
    {
        $this->delivey_date_request = $delivey_date_request;

        return $this;
    }

    public function getDeliveryDateConfirmed(): ?\DateTime
    {
        return $this->delivery_date_confirmed;
    }

    public function setDeliveryDateConfirmed(?\DateTime $delivery_date_confirmed): static
    {
        $this->delivery_date_confirmed = $delivery_date_confirmed;

        return $this;
    }

    public function getIsoRowNote(): ?string
    {
        return $this->iso_row_note;
    }

    public function setIsoRowNote(?string $iso_row_note): static
    {
        $this->iso_row_note = $iso_row_note;

        return $this;
    }

    public function getProductionRowNote(): ?string
    {
        return $this->production_row_note;
    }

    public function setProductionRowNote(?string $production_row_note): static
    {
        $this->production_row_note = $production_row_note;

        return $this;
    }

    public function getAdministrationRowNote(): ?string
    {
        return $this->administration_row_note;
    }

    public function setAdministrationRowNote(?string $administration_row_note): static
    {
        $this->administration_row_note = $administration_row_note;

        return $this;
    }
}
