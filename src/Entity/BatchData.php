<?php

namespace App\Entity;

use App\Repository\BatchDataRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BatchDataRepository::class)]
class BatchData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?\DateTime $delivery_date = null;

    #[ORM\Column]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $amount = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $currency_exchange = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?\DateTime $payment_date = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?\DateTime $sea_port_date = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $declared_gross_weight = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $declared_net_weight = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $declared_average_weight = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $founded_gross_weight = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $founded_net_weight = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $founded_average_weight = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?string $container_code = null;

    #[ORM\ManyToOne(inversedBy: 'batchData')]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?SeaPort $sea_port = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $shipping_cost = null;

    #[ORM\ManyToOne(inversedBy: 'batchData')]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?Pallet $pallet = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?int $pallet_number = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_data_list', 'batch_data_detail', 'batch_list', 'batch_detail'])]
    private ?float $pallet_weight = null;

    #[ORM\ManyToOne(inversedBy: 'batchData')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_data_detail'])]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'batchData')]
    private ?ShipmentCondition $shipmentCondition = null;

    #[ORM\ManyToOne]
    private ?Contact $shipment_subcontractor = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeliveryDate(): ?\DateTime
    {
        return $this->delivery_date;
    }

    public function setDeliveryDate(?\DateTime $delivery_date): static
    {
        $this->delivery_date = $delivery_date;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

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

    public function getPaymentDate(): ?\DateTime
    {
        return $this->payment_date;
    }

    public function setPaymentDate(?\DateTime $payment_date): static
    {
        $this->payment_date = $payment_date;

        return $this;
    }

    public function getSeaPortDate(): ?\DateTime
    {
        return $this->sea_port_date;
    }

    public function setSeaPortDate(?\DateTime $sea_port_date): static
    {
        $this->sea_port_date = $sea_port_date;

        return $this;
    }

    public function getDeclaredGrossWeight(): ?float
    {
        return $this->declared_gross_weight;
    }

    public function setDeclaredGrossWeight(?float $declared_gross_weight): static
    {
        $this->declared_gross_weight = $declared_gross_weight;

        return $this;
    }

    public function getDeclaredNetWeight(): ?float
    {
        return $this->declared_net_weight;
    }

    public function setDeclaredNetWeight(?float $declared_net_weight): static
    {
        $this->declared_net_weight = $declared_net_weight;

        return $this;
    }

    public function getDeclaredAverageWeight(): ?float
    {
        return $this->declared_average_weight;
    }

    public function setDeclaredAverageWeight(?float $declared_average_weight): static
    {
        $this->declared_average_weight = $declared_average_weight;

        return $this;
    }

    public function getFoundedGrossWeight(): ?float
    {
        return $this->founded_gross_weight;
    }

    public function setFoundedGrossWeight(?float $founded_gross_weight): static
    {
        $this->founded_gross_weight = $founded_gross_weight;

        return $this;
    }

    public function getFoundedNetWeight(): ?float
    {
        return $this->founded_net_weight;
    }

    public function setFoundedNetWeight(?float $founded_net_weight): static
    {
        $this->founded_net_weight = $founded_net_weight;

        return $this;
    }

    public function getFoundedAverageWeight(): ?float
    {
        return $this->founded_average_weight;
    }

    public function setFoundedAverageWeight(?float $founded_average_weight): static
    {
        $this->founded_average_weight = $founded_average_weight;

        return $this;
    }

    public function getContainerCode(): ?string
    {
        return $this->container_code;
    }

    public function setContainerCode(?string $container_code): static
    {
        $this->container_code = $container_code;

        return $this;
    }

    public function getSeaPort(): ?SeaPort
    {
        return $this->sea_port;
    }

    public function setSeaPort(?SeaPort $sea_port): static
    {
        $this->sea_port = $sea_port;

        return $this;
    }

    public function getShippingCost(): ?float
    {
        return $this->shipping_cost;
    }

    public function setShippingCost(?float $shipping_cost): static
    {
        $this->shipping_cost = $shipping_cost;

        return $this;
    }

    public function getPallet(): ?Pallet
    {
        return $this->pallet;
    }

    public function setPallet(?Pallet $pallet): static
    {
        $this->pallet = $pallet;

        return $this;
    }

    public function getPalletNumber(): ?int
    {
        return $this->pallet_number;
    }

    public function setPalletNumber(?int $pallet_number): static
    {
        $this->pallet_number = $pallet_number;

        return $this;
    }

    public function getPalletWeight(): ?float
    {
        return $this->pallet_weight;
    }

    public function setPalletWeight(?float $pallet_weight): static
    {
        $this->pallet_weight = $pallet_weight;

        return $this;
    }

    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    public function setBatch(?Batch $batch): static
    {
        $this->batch = $batch;

        return $this;
    }

    public function getShipmentCondition(): ?ShipmentCondition
    {
        return $this->shipmentCondition;
    }

    public function setShipmentCondition(?ShipmentCondition $shipmentCondition): static
    {
        $this->shipmentCondition = $shipmentCondition;

        return $this;
    }

    public function getShipmentSubcontractor(): ?Contact
    {
        return $this->shipment_subcontractor;
    }

    public function setShipmentSubcontractor(?Contact $shipment_subcontractor): static
    {
        $this->shipment_subcontractor = $shipment_subcontractor;

        return $this;
    }
}
