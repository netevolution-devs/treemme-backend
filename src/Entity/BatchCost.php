<?php

namespace App\Entity;

use App\Repository\BatchCostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchCostRepository::class)]
class BatchCost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'batchCosts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'batchCosts')]
    private ?BatchCostType $batch_cost_type = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\Column(nullable: true)]
    private ?float $cost = null;

    #[ORM\ManyToOne(inversedBy: 'batchCosts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    #[ORM\Column(nullable: true)]
    private ?float $currency_cost = null;

    #[ORM\Column]
    private ?float $currency_exchange = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cost_note = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBatchCostType(): ?BatchCostType
    {
        return $this->batch_cost_type;
    }

    public function setBatchCostType(?BatchCostType $batch_cost_type): static
    {
        $this->batch_cost_type = $batch_cost_type;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrencyCost(): ?float
    {
        return $this->currency_cost;
    }

    public function setCurrencyCost(?float $currency_cost): static
    {
        $this->currency_cost = $currency_cost;

        return $this;
    }

    public function getCurrencyExchange(): ?float
    {
        return $this->currency_exchange;
    }

    public function setCurrencyExchange(float $currency_exchange): static
    {
        $this->currency_exchange = $currency_exchange;

        return $this;
    }

    public function getCostNote(): ?string
    {
        return $this->cost_note;
    }

    public function setCostNote(?string $cost_note): static
    {
        $this->cost_note = $cost_note;

        return $this;
    }
}
